<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\AuditLog;
use App\Models\Clearance;
use App\Models\EnrollmentAgreement;
use App\Models\TransactionLedger;
use App\Models\User;
use App\Notifications\ClearanceStatusUpdatedNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private PayMongoService $gateway,
        private FeeAssessmentService $fees,
        private AdmissionService $admissions,
    ) {
    }

    /**
     * Start a full-balance checkout. Returns the hosted checkout URL.
     * Cancels stale Pending rows first; on gateway failure the fresh row
     * is marked Failed and the exception is rethrown for the route to flash.
     */
    public function startCheckout(User $user, float $balance): string
    {
        $row = Cache::lock("ledger-checkout-{$user->id}", 10)->block(5, function () use ($user, $balance) {
            TransactionLedger::where('user_id', $user->id)
                ->where('gateway', 'paymongo')->where('status', 'Pending')
                ->update(['status' => 'Cancelled']);

            return TransactionLedger::create([
                'user_id' => $user->id,
                'reference_no' => 'PMG-' . strtoupper(Str::random(10)),
                'amount' => $balance,
                'status' => 'Pending',
                'gateway' => 'paymongo',
                'remarks' => 'Online payment via PayMongo checkout.',
            ]);
        });

        try {
            $session = $this->gateway->createCheckoutSession(
                $user,
                (int) round($balance * 100),
                'AITSA Tuition & Fees — ' . ($user->login_id ?? $user->name)
            );
        } catch (PaymentGatewayException $e) {
            $row->update(['status' => 'Failed']);
            throw $e;
        }

        $row->update(['checkout_session_id' => $session['id']]);

        return $session['checkout_url'];
    }

    /**
     * Verify the student's latest Pending gateway payment against PayMongo.
     * Settles the row and auto-approves the cashier clearance when the
     * balance reaches zero. Safe to call repeatedly.
     *
     * @return array{ok: bool, message: string}
     */
    public function verifyLatestPending(User $user): array
{
    // Get this user's most recent unpaid PayMongo transaction, if any.
    $row = TransactionLedger::where('user_id', $user->id)
        ->where('gateway', 'paymongo')->where('status', 'Pending')
        ->latest()->first();

    // Nothing to verify — either no payment was ever started, or it
    // was never linked to a real PayMongo checkout session.
    if (! $row || ! $row->checkout_session_id) {
        return ['ok' => false, 'message' => 'No pending online payment to verify.'];
    }

    // Ask PayMongo directly: has this checkout session actually been paid?
    $session = $this->gateway->retrieveCheckoutSession($row->checkout_session_id);
    if (! $this->gateway->sessionIsPaid($session)) {
        return ['ok' => false, 'message' => 'The gateway has not confirmed this payment yet. If you completed payment, try Verify Payment again in a moment.'];
    }

    // Confirmed paid — hand off to the shared settlement logic.
    return $this->settleRow($row);
}
/**
 * Start a checkout for the slot-reservation fee.
 * Only for users who haven't reserved yet — prevents double charging.
 * Returns the PayMongo hosted checkout URL to redirect the user to.
 *
 * @param  string|null  $successUrl  Where PayMongo sends the user back to after a successful
 *                                   payment. Needed for applicants, who aren't logged in yet
 *                                   and can't use the normal /ledger return route.
 * @param  string|null  $cancelUrl   Where PayMongo sends the user back to if they cancel.
 */
public function startReservationCheckout(User $user, ?string $successUrl = null, ?string $cancelUrl = null): string
{
    // Reservation fee amount is configurable via Settings (Cashier -> Billing Setup), not hardcoded.
    $reservationFee = (int) \App\Models\Setting::get('reservation_fee', '500');

    // Guard: don't let an already-reserved student pay again.
    if ($user->is_reserved) {
        throw new PaymentGatewayException('You have already reserved your slot.');
    }

    // Enrollment-agreement gate: don't go to PayMongo until the applicant
    // has signed their enrollment agreement natively in-app (canvas
    // signature + audit trail). Once AgreementController::submit() records
    // the signature, this same method runs again, this time with
    // hasSigned() === true, and falls through to PayMongo below.
    if (! EnrollmentAgreement::hasSigned($user)) {
        return URL::signedRoute('agreement.sign', ['user' => $user->id]);
    }

    // Create the actual PayMongo checkout session (amount is in centavos).
    // Custom success/cancel URLs let applicants (who aren't logged in yet)
    // get routed back to a signed applicant-facing page instead of /ledger.
    $session = $this->gateway->createCheckoutSession(
        $user,
        $reservationFee * 100,
        'AITSA Slot Reservation — ' . ($user->login_id ?? $user->name),
        $successUrl,
        $cancelUrl
    );
    // Record this as a Pending transaction so we can match it later,
    // either via the webhook or the manual "verify on return" check.
    // fee_type = 'reservation' distinguishes it from regular tuition payments.
    TransactionLedger::create([
        'user_id' => $user->id,
        'gateway' => 'paymongo',
        'checkout_session_id' => $session['id'],
        'amount' => $reservationFee,
        'fee_type' => 'reservation',
        'status' => 'Pending',
        'reference_no' => 'RES-' . strtoupper(Str::random(10)),
    ]);

    return $session['checkout_url'];
}
/**
 * Called by the PayMongo webhook when checkout_session.payment.paid fires.
 * This is the PRIMARY, reliable settlement path — it works even if the
 * student closes their browser before returning from checkout.
 * The webhook controller already verified the request's signature before
 * calling this, so we trust the checkoutSessionId here.
 * @return array{ok: bool, message: string}
 */
public function settleByCheckoutSessionId(string $checkoutSessionId): array
{
    $row = TransactionLedger::where('checkout_session_id', $checkoutSessionId)
        ->where('gateway', 'paymongo')->where('status', 'Pending')
        ->first();

        // Could happen if the webhook fires twice, or for a session we don't
        // recognize — fail quietly, no error thrown.
    if (! $row) {
        return ['ok' => false, 'message' => 'No matching pending payment found for this session.'];
    }

    return $this->settleRow($row);
}
/**
 * Shared settlement logic used by BOTH the webhook path and the
 * "verify on return" path — keeps the two in sync so payments are
 * never processed differently depending on which path caught them first.
 *
 * What it does:
 *  1. Marks the transaction as Settled.
 *  2. If this was a reservation fee, flips the student's is_reserved flag
 *     to true — this is what makes the reservation fee start appearing in their
 *     regular tuition assessment (as its own paid line, not merged into tuition).
 *  3. Logs the payment to the audit trail.
 *  4. If the student's balance is now fully paid, auto-approves their
 *     cashier clearance so they don't need to be cleared manually.
 * 
 * @return array{ok: bool, message: string}
*/
private function settleRow(TransactionLedger $row): array
{
    // Step 1: mark this specific transaction as paid.
    $row->update(['status' => 'Settled', 'paid_at' => now()]);

    // Step 2: reservation payments unlock the reservation fee line item
    // in FeeAssessmentService::breakdownFor() for future assessments, and —
    // for an applicant — immediately activate their student account (no
    // Registrar verify/decline step).
    if ($row->fee_type === 'reservation') {
        $row->user->update(['is_reserved' => true]);
        $this->admissions->activateStudentAccount($row->user);
    }

    $user = $row->user;

    // Step 3: audit trail — who paid, how much, and for what reference.
    AuditLog::record('Payment Settled', 'Online payment of ₱' . number_format((float) $row->amount, 2) . ' settled via PayMongo for ' . $user->name . ' (' . ($user->login_id ?? 'N/A') . '). Ref ' . $row->reference_no . '.', 'TransactionLedger', $row->id);

    $message = 'Payment received — ₱' . number_format((float) $row->amount, 2) . ' settled.';
    // Step 4: if this payment brought the student's balance to zero,
    // auto-approve their cashier clearance so registrar/cashier don't
    // need to manually check and approve it.
    if ($this->fees->breakdownFor($user)['fully_paid']) {
        $clearance = Clearance::currentFor($user);
        if ($clearance && $clearance->cashier_status !== 'Approved') {
            $clearance->update(['cashier_status' => 'Approved']);
            AuditLog::record('Cashier Cleared (Gateway)', 'Cashier clearance auto-approved for ' . $user->name . ' (' . ($user->login_id ?? 'N/A') . ') after gateway-verified full payment.', 'Clearance', $clearance->id);
                $message .= ' Your cashier clearance has been approved.';
                \App\Support\SafeNotify::send($user, new ClearanceStatusUpdatedNotification('Cashier', 'Approved'));
            }
    }

    return ['ok' => true, 'message' => $message];
}

    public function cancelLatestPending(User $user): void
    {
        TransactionLedger::where('user_id', $user->id)
            ->where('gateway', 'paymongo')->where('status', 'Pending')
            ->latest()->first()?->update(['status' => 'Cancelled']);
    }
}