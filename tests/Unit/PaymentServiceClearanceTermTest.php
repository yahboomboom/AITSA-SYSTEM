<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\Setting;
use App\Models\TransactionLedger;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentServiceClearanceTermTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_payment_approves_only_the_current_terms_cashier_status(): void
    {
        Mail::fake();
        $student = User::factory()->create(['role' => 'student', 'major' => null]);

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        // FeeAssessmentService::breakdownFor() falls back to a flat
        // tuition_fee_flat setting (default 15000) regardless of whether
        // the student has a program — see FeeAssessmentServiceTest::
        // test_student_without_program_owes_misc_only, which shows a
        // major=null student is NOT misc-fee-only under the default
        // settings. Zero out the flat tuition here so tuition falls back
        // to units * rate; with no program (major = null) units is 0, so
        // tuition is 0 and the only assessed amount is the misc fee
        // (default 1500), letting a single settled transaction for that
        // amount bring the balance to zero.
        Setting::put('tuition_fee_flat', '0');
        Setting::clearCache();
        $past = Clearance::initializeFor($student->id, '2026-2027', 1, ['cashier_status' => 'Pending']);

        Setting::put('semester', '2');
        Setting::clearCache();
        $current = Clearance::initializeFor($student->id, '2026-2027', 2, ['cashier_status' => 'Pending']);
        $row = TransactionLedger::create([
            'user_id' => $student->id,
            'gateway' => 'paymongo',
            'checkout_session_id' => 'cs_term_test',
            'amount' => 1500,
            'status' => 'Pending',
            'reference_no' => 'TXN-TERM-TEST',
        ]);

        app(PaymentService::class)->settleByCheckoutSessionId('cs_term_test');

        $this->assertSame('Pending', $past->fresh()->cashier_status);
        $this->assertSame('Approved', $current->fresh()->cashier_status);
    }
}
