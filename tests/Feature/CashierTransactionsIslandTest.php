<?php

namespace Tests\Feature;

use App\Models\TransactionLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierTransactionsIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_page_renders_the_react_island_mount_point_with_real_data(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Ledger Test Student']);
        TransactionLedger::factory()->create([
            'user_id' => $student->id,
            'reference_no' => 'REF-VERIFY-001',
            'amount' => 1234,
            'status' => 'Settled',
        ]);

        $response = $this->actingAs($cashier)->withSession(['auth.password_confirmed_at' => time()])->get('/cashier/transactions');

        $response->assertOk();
        $response->assertSee('id="cashier-transactions-root"', false);
        $response->assertSee('Ledger Test Student');
        $response->assertSee('REF-VERIFY-001');
    }

    public function test_transactions_page_handles_no_records_yet(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->withSession(['auth.password_confirmed_at' => time()])->get('/cashier/transactions');

        $response->assertOk();
        $response->assertSee('&quot;rows&quot;:[]', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/cashier/transactions')->assertRedirect();
    }

    public function test_non_cashier_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/cashier/transactions')->assertForbidden();
    }
}
