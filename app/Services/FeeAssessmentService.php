<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Setting;
use App\Models\TransactionLedger;
use App\Models\User;

class FeeAssessmentService
{
    public function __construct(private EnrollmentService $enrollment)
    {
    }

    /**
     * Full money picture for a student:
     * units, rate, tuition, discount_name, discount_percent, discount_amount,
     * misc, assessment, paid, balance, fully_paid.
     */
    public function breakdownFor(User $user): array
    {
        $reservationFee = (int) Setting::get('reservation_fee', '500');/**reservation fee of student, set in Cashier > Billing Setup */
        $flatTuition = (int) Setting::get('tuition_fee_flat', '15000');/**tuition fee of school */
        $tesdaTuition = (int) Setting::get('tesda_tuition_fee', '1500');/**flat tuition for TESDA Short-Term Programs, set in Cashier > Billing Setup */
        $rate = (int) Setting::get('tuition_per_unit', '300');
        $misc = (int) Setting::get('misc_fee', '1500');

        $units = $this->plannedUnits($user);

        // TESDA Short-Term Programs use a flat tuition amount instead of the
        // regular flat/per-unit tuition — configurable in Billing Setup.
        if (strtoupper((string) $user->program_level) === 'TESDA') {
            $tuition = (float) $tesdaTuition;
        } else {
            $tuition = $flatTuition > 0 ? (float) $flatTuition : round($units * $rate, 2);/**tuiton fee */
        }

        $discountType = $user->discountType;
        $percent = $discountType->percent ?? 0;
        $discountAmount = round($tuition * $percent / 100, 2);

        $reservationFee = $user->is_reserved ? $reservationFee : 0;/**if the student reserve the slot reservation fee */
        // NOTE: reservationFee is its own line item, kept separate from $tuition
        // (never added into it). It's only added once, here, to the overall
        // assessment total — and it's already been paid at application time,
        // so it nets out against $paid below instead of being billed again.
        $assessment = round($tuition - $discountAmount + $misc + $reservationFee, 2);/**calculation fee */
        $paid = round((float) TransactionLedger::where('user_id', $user->id)
            ->where('status', 'Settled')->sum('amount'), 2);
        $balance = round(max($assessment - $paid, 0), 2);

        return [
            'units' => $units,
            'rate' => $rate,
            'tuition' => $tuition,
            'reservation_fee' => $reservationFee,/**reservation fee */
            'discount_name' => $discountType->name ?? null,
            'discount_percent' => $percent,
            'discount_amount' => $discountAmount,
            'misc' => $misc,
            'assessment' => $assessment,
            'paid' => $paid,
            'balance' => $balance,
            'fully_paid' => $balance == 0.0 && $assessment > 0,
        ];
    }

    /**
     * Units the student is set to take this term: their committed enrollment
     * if one exists, otherwise the regular block plan, otherwise the
     * irregular student's eligible subjects.
     */
    private function plannedUnits(User $user): int
    {
        $active = $this->enrollment->activeEnrollment($user);
        if ($active && $active->status !== 'rejected') {
            return (int) $active->sections()->with('subject')->get()
                ->sum(fn (Section $s) => $s->subject->units);
        }

        if (! $user->program()) {
            return 0;
        }

        if (! $user->isIrregularStudent()) {
            $block = $this->enrollment->blockFor($user);

            return $block
                ? (int) $block['sections']->sum(fn (Section $s) => $s->subject->units)
                : 0;
        }

        return (int) $this->enrollment->catalogueFor($user)
            ->where('eligible', true)
            ->sum('units');
    }
}