<?php

namespace App\Console\Commands;

use App\Models\Clearance;
use App\Models\User;
use App\Services\FeeAssessmentService;
use Illuminate\Console\Command;

class FixIncorrectClearances extends Command
{
    protected $signature = 'clearance:fix-incorrect';

    protected $description = 'Re-check every Approved clearance against the student\'s actual balance, and revert any that were incorrectly marked Approved while a balance still remains.';

    public function handle(FeeAssessmentService $fees): int
    {
        // Only look at clearances currently marked Approved — these are
        // the only ones that could be wrongly cleared.
        $approvedClearances = Clearance::where('cashier_status', 'Approved')->get();

        $fixedCount = 0;

        foreach ($approvedClearances as $clearance) {
            $user = User::find($clearance->user_id);

            // Skip if the user record is somehow missing.
            if (! $user) {
                continue;
            }

            // Recompute the student's real assessment and balance.
            $breakdown = $fees->breakdownFor($user);

            // If they are NOT actually fully paid, this clearance was
            // wrongly approved (likely by the old buggy logic) — revert it.
            if (! $breakdown['fully_paid']) {
                $clearance->update([
                    'cashier_status' => 'Pending',
                    'remarks' => 'Reverted by system audit — balance of ₱' . number_format($breakdown['balance'], 2) . ' remains unpaid.',
                ]);

                $this->line('Reverted: ' . $user->name . ' (₱' . number_format($breakdown['balance'], 2) . ' still owed)');
                $fixedCount++;
            }
        }

        $this->info("Done. {$fixedCount} incorrectly-approved clearance(s) reverted to Pending.");

        return self::SUCCESS;
    }
}