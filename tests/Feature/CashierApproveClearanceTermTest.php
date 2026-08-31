<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierApproveClearanceTermTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_payment_marks_only_the_current_terms_cashier_status(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student']);

        // Keep the fee assessment simple and fully payable by the posted
        // amount below, so `approveClearance()` takes its "fully paid"
        // branch and this test isolates the term-scoping fix rather than
        // the fee assessment's own business logic.
        Setting::put('tuition_fee_flat', '500');
        Setting::put('misc_fee', '500');
        Setting::put('reservation_fee', '0');

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
        $past = Clearance::initializeFor($student->id, '2026-2027', 1, ['cashier_status' => 'Pending']);

        Setting::put('semester', '2');
        Setting::clearCache();
        $current = Clearance::initializeFor($student->id, '2026-2027', 2, ['cashier_status' => 'Pending']);

        $this->actingAs($cashier)->post('/cashier/approve', [
            'user_id' => $student->id,
            'reference_no' => 'OR-TERM-TEST-1',
            'amount' => '1000',
        ]);

        $this->assertSame('Pending', $past->fresh()->cashier_status);
        $this->assertSame('Approved', $current->fresh()->cashier_status);
    }
}
