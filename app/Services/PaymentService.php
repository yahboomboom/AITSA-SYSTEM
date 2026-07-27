<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\AuditLog;
use App\Models\Clearance;
use App\Models\TransactionLedger;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private PayMongoService $gateway,
        private FeeAssessmentService $fees,
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
        $row = TransactionLedger::where('user_id', $user->id)
            ->where('gateway', 'paymongo')->where('status', 'Pending')
            ->latest()->first();

        if (! $row || ! $row->checkout_session_id) {
            return ['ok' => false, 'message' => 'No pending online payment to verify.'];
        }

        $session = $this->gateway->retrieveCheckoutSession($row->checkout_session_id);
        if (! $this->gateway->sessionIsPaid($session)) {
            return ['ok' => false, 'message' => 'The gateway has not confirmed this payment yet. If you completed payment, try Verify Payment again in a moment.'];
        }

        $row->update(['status' => 'Settled', 'paid_at' => now()]);
        AuditLog::record('Payment Settled', 'Online payment of ₱' . number_format((float) $row->amount, 2) . ' settled via PayMongo for ' . $user->name . ' (' . ($user->login_id ?? 'N/A') . '). Ref ' . $row->reference_no . '.', 'TransactionLedger', $row->id);

        $message = 'Payment received — ₱' . number_format((float) $row->amount, 2) . ' settled.';

        if ($this->fees->breakdownFor($user)['fully_paid']) {
            $clearance = Clearance::where('user_id', $user->id)->first();
            if ($clearance && $clearance->cashier_status !== 'Approved') {
                $clearance->update(['cashier_status' => 'Approved']);
                AuditLog::record('Cashier Cleared (Gateway)', 'Cashier clearance auto-approved for ' . $user->name . ' (' . ($user->login_id ?? 'N/A') . ') after gateway-verified full payment.', 'Clearance', $clearance->id);
                $message .= ' Your cashier clearance has been approved.';
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
