<?php

namespace Tests\Feature;

use App\Models\TransactionLedger;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_ledger_shows_breakdown_and_own_history_only(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'reference_no' => 'PMG-MINE123456', 'status' => 'Settled', 'paid_at' => now(),
        ]);
        TransactionLedger::factory()->create(['reference_no' => 'PMG-OTHERS7890']);

        $response = $this->actingAs($student)->get('/ledger');

        $response->assertOk()
            ->assertSee('id="payment-root"', false)
            ->assertSee('&quot;referenceNo&quot;:&quot;PMG-MINE123456&quot;', false)
            ->assertDontSee('PMG-OTHERS7890')
            ->assertDontSee('mockPay');
    }

    public function test_pending_gateway_row_shows_verify_button(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        TransactionLedger::factory()->create(['user_id' => $student->id, 'status' => 'Pending']);

        $this->actingAs($student)->get('/ledger')
            ->assertOk()
            ->assertSee('&quot;hasPendingGateway&quot;:true', false);
    }
}
