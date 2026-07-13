<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TransactionLedger;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private TransactionLedger $row;

    private function setUpPendingPayment(): void
    {
        $this->seed(ProgramSeeder::class);
        $this->student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1, 'units' => 5]);
        Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);
        Clearance::create([
            'user_id' => $this->student->id,
            'admission_status' => 'Approved', 'chair_status' => 'Pending',
            'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
        ]);
        $this->row = TransactionLedger::factory()->create([
            'user_id' => $this->student->id, 'status' => 'Pending',
            'amount' => 3000.00, 'checkout_session_id' => 'cs_test_abc',
        ]);
    }

    private function fakeSession(bool $paid): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_test_abc' => Http::response([
                'data' => [
                    'id' => 'cs_test_abc',
                    'attributes' => ['payments' => $paid ? [['attributes' => ['status' => 'paid']]] : []],
                ],
            ], 200),
        ]);
    }

    public function test_paid_session_settles_row_and_approves_cashier(): void
    {
        $this->setUpPendingPayment();
        $this->fakeSession(true);

        $response = $this->actingAs($this->student)->get('/ledger/payment/return');

        $response->assertRedirect(route('ledger'));
        $response->assertSessionHas('success');
        $row = $this->row->fresh();
        $this->assertSame('Settled', $row->status);
        $this->assertNotNull($row->paid_at);
        $this->assertSame('Approved', Clearance::where('user_id', $this->student->id)->first()->cashier_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Payment Settled']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Cashier Cleared (Gateway)']);
    }

    public function test_unpaid_session_keeps_row_pending(): void
    {
        $this->setUpPendingPayment();
        $this->fakeSession(false);

        $response = $this->actingAs($this->student)->get('/ledger/payment/return');

        $response->assertRedirect(route('ledger'));
        $response->assertSessionHas('error');
        $this->assertSame('Pending', $this->row->fresh()->status);
        $this->assertSame('Pending', Clearance::where('user_id', $this->student->id)->first()->cashier_status);
    }

    public function test_cancel_route_cancels_pending_row(): void
    {
        $this->setUpPendingPayment();

        $response = $this->actingAs($this->student)->get('/ledger/payment/cancel');

        $response->assertRedirect(route('ledger'));
        $this->assertSame('Cancelled', $this->row->fresh()->status);
    }

    public function test_verify_route_settles_like_return(): void
    {
        $this->setUpPendingPayment();
        $this->fakeSession(true);

        $this->actingAs($this->student)->post('/ledger/verify')->assertRedirect(route('ledger'));

        $this->assertSame('Settled', $this->row->fresh()->status);
    }

    public function test_return_with_no_pending_row_is_a_noop(): void
    {
        $this->setUpPendingPayment();
        $this->row->update(['status' => 'Settled', 'paid_at' => now()]);
        Http::fake();

        $response = $this->actingAs($this->student)->get('/ledger/payment/return');

        $response->assertRedirect(route('ledger'));
        Http::assertNothingSent();
        $this->assertSame(1, TransactionLedger::count());
    }

    public function test_partial_settlement_does_not_approve_cashier(): void
    {
        $this->setUpPendingPayment();
        // Pretend the pending checkout was for less than the full balance
        $this->row->update(['amount' => 100.00]);
        $this->fakeSession(true);

        $this->actingAs($this->student)->get('/ledger/payment/return');

        $this->assertSame('Settled', $this->row->fresh()->status);
        $this->assertSame('Pending', Clearance::where('user_id', $this->student->id)->first()->cashier_status);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'Cashier Cleared (Gateway)']);
    }
}
