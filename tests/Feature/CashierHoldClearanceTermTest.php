<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierHoldClearanceTermTest extends TestCase
{
    use RefreshDatabase;

    public function test_holding_a_clearance_holds_only_the_current_term(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student']);

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
        $past = Clearance::initializeFor($student->id, '2026-2027', 1, ['cashier_status' => 'Approved']);

        Setting::put('semester', '2');
        Setting::clearCache();
        $current = Clearance::initializeFor($student->id, '2026-2027', 2, ['cashier_status' => 'Pending']);

        $this->actingAs($cashier)->post('/cashier/hold', [
            'user_id' => $student->id,
            'remarks' => 'Unpaid balance from last term.',
        ]);

        $this->assertSame('Approved', $past->fresh()->cashier_status);
        $this->assertSame('Hold', $current->fresh()->cashier_status);
    }
}
