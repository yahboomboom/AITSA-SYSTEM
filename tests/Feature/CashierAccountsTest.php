<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_account_has_no_quick_approve_form_writing_fake_data(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'admission_status' => 'Approved', 'chair_status' => 'Pending',
            'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
        ]);

        $response = $this->actingAs($cashier)->get('/cashier/accounts');

        $response->assertOk();
        $response->assertDontSee('Quick Approve');
        $response->assertDontSee('₱ 3,500.00', false);
        $response->assertDontSee('name="amount"', false);
        $response->assertSee('Review in Cashier Hub');
        $response->assertSee(route('cashier.dashboard'), false);
    }
}
