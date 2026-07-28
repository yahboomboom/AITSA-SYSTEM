<?php

namespace Tests\Feature;

use App\Http\Controllers\AuthController;
use App\Models\Clearance;
use App\Models\TransactionLedger;
use App\Models\User;
use App\Services\FeeAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierDashboardBillingTest extends TestCase
{
    use RefreshDatabase;

    private function getContext(): array
    {
        $view = app(AuthController::class)->showCashierDashboard(app(FeeAssessmentService::class));

        return $view->getData()['context'];
    }

    public function test_context_uses_real_fee_assessment_balance_not_flat_fee(): void
    {
        $student = User::factory()->create(['role' => 'student', 'major' => null]);
        Clearance::create([
            'user_id' => $student->id,
            'admission_status' => 'Approved', 'chair_status' => 'Pending',
            'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
        ]);

        $context = $this->getContext();
        $row = collect($context['rows'])->firstWhere('userId', $student->id);

        $this->assertNotNull($row);
        // A student with no program owes misc-fee-only (₱1,500 by default settings) — this
        // specific, non-round number proves real FeeAssessmentService wiring, not the old
        // hardcoded flat ₱3,500.
        $this->assertEqualsWithDelta(1500.0, $row['balance'], 0.001);
        $this->assertNotEqualsWithDelta(3500.0, $row['balance'], 0.001);
    }

    public function test_context_stats_use_real_settled_transaction_sum(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        TransactionLedger::factory()->create(['user_id' => $student->id, 'status' => 'Settled', 'amount' => 2000.00]);
        TransactionLedger::factory()->create(['user_id' => $student->id, 'status' => 'Pending', 'amount' => 999.00]);

        $context = $this->getContext();

        $this->assertEqualsWithDelta(2000.0, $context['stats']['settledBase'], 0.001);
        $this->assertSame(1, $context['stats']['settledCount']);
    }

    public function test_row_shows_latest_settled_reference_no(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id, 'admission_status' => 'Approved',
            'chair_status' => 'Pending', 'cashier_status' => 'Approved', 'registrar_status' => 'Pending',
        ]);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Settled', 'amount' => 1500.00, 'reference_no' => 'OR-0001',
        ]);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Settled', 'amount' => 500.00, 'reference_no' => 'OR-0002',
        ]);

        $context = $this->getContext();
        $row = collect($context['rows'])->firstWhere('userId', $student->id);

        $this->assertSame('OR-0002', $row['referenceNo']);
        $this->assertTrue($row['isApproved']);
    }
}
