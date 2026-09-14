<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TransactionLedger;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithBalance(): User
    {
        $this->seed(ProgramSeeder::class);
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1, 'units' => 5]);
        Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);

        return $student; // balance: 5*300 + 1500 = 3000.00
    }

    private function fakeCheckoutCreated(): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => ['id' => 'cs_test_abc', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_test_abc']],
            ], 200),
        ]);
    }

    public function test_checkout_creates_pending_row_and_redirects_to_gateway(): void
    {
        $this->fakeCheckoutCreated();
        $student = $this->studentWithBalance();

        $response = $this->actingAs($student)->post('/ledger/checkout');

        $response->assertRedirect('https://checkout.paymongo.com/cs_test_abc');
        $row = TransactionLedger::where('user_id', $student->id)->first();
        $this->assertSame('Pending', $row->status);
        $this->assertSame('paymongo', $row->gateway);
        $this->assertSame('cs_test_abc', $row->checkout_session_id);
        $this->assertEqualsWithDelta(3000.0, (float) $row->amount, 0.001);
        Http::assertSent(fn ($r) => $r->data()['data']['attributes']['line_items'][0]['amount'] === 300000);
    }

    public function test_zero_balance_blocks_checkout(): void
    {
        Http::fake();
        $student = $this->studentWithBalance();
        TransactionLedger::factory()->create(['user_id' => $student->id, 'status' => 'Settled', 'amount' => 3000.00]);

        $response = $this->actingAs($student)->from('/ledger')->post('/ledger/checkout');

        $response->assertRedirect('/ledger');
        $response->assertSessionHas('error');
        $this->assertSame(1, TransactionLedger::count()); // no new row
        Http::assertNothingSent();
    }

    public function test_new_checkout_cancels_stale_pending_rows(): void
    {
        $this->fakeCheckoutCreated();
        $student = $this->studentWithBalance();
        $stale = TransactionLedger::factory()->create(['user_id' => $student->id, 'status' => 'Pending']);

        $this->actingAs($student)->post('/ledger/checkout');

        $this->assertSame('Cancelled', $stale->fresh()->status);
    }

    public function test_gateway_failure_marks_row_failed_with_error_flash(): void
    {
        Http::fake(['api.paymongo.com/*' => Http::response(['errors' => []], 500)]);
        $student = $this->studentWithBalance();

        $response = $this->actingAs($student)->from('/ledger')->post('/ledger/checkout');

        $response->assertRedirect('/ledger');
        $response->assertSessionHas('error');
        $this->assertSame('Failed', TransactionLedger::where('user_id', $student->id)->first()->status);
    }

    public function test_guest_is_redirected(): void
    {
        Http::fake();
        $this->post('/ledger/checkout')->assertRedirect();
        Http::assertNothingSent();
    }

    public function test_down_payment_checkout_charges_only_the_remaining_down_payment_amount(): void
    {
        $this->fakeCheckoutCreated();
        \App\Models\Setting::put('down_payment_percent', '30');
        $student = $this->studentWithBalance(); // balance/assessment: 3000.00, down payment required: 900.00

        $response = $this->actingAs($student)->post('/ledger/checkout', ['type' => 'down_payment']);

        $response->assertRedirect('https://checkout.paymongo.com/cs_test_abc');
        $row = TransactionLedger::where('user_id', $student->id)->first();
        $this->assertEqualsWithDelta(900.0, (float) $row->amount, 0.001);
        Http::assertSent(fn ($r) => $r->data()['data']['attributes']['line_items'][0]['amount'] === 90000);
    }

    public function test_down_payment_checkout_charges_only_the_remaining_amount_after_a_partial_payment(): void
    {
        $this->fakeCheckoutCreated();
        \App\Models\Setting::put('down_payment_percent', '30');
        $student = $this->studentWithBalance(); // assessment 3000.00, down payment required 900.00
        TransactionLedger::factory()->create(['user_id' => $student->id, 'status' => 'Settled', 'amount' => 500.00]);

        $response = $this->actingAs($student)->post('/ledger/checkout', ['type' => 'down_payment']);

        $response->assertRedirect('https://checkout.paymongo.com/cs_test_abc');
        $row = TransactionLedger::where('user_id', $student->id)->where('status', 'Pending')->first();
        $this->assertEqualsWithDelta(400.0, (float) $row->amount, 0.001);
    }

    public function test_down_payment_checkout_is_blocked_once_the_down_payment_is_already_met(): void
    {
        Http::fake();
        \App\Models\Setting::put('down_payment_percent', '30');
        $student = $this->studentWithBalance();
        TransactionLedger::factory()->create(['user_id' => $student->id, 'status' => 'Settled', 'amount' => 900.00]);

        $response = $this->actingAs($student)->from('/ledger')->post('/ledger/checkout', ['type' => 'down_payment']);

        $response->assertRedirect('/ledger');
        $response->assertSessionHas('error');
        Http::assertNothingSent();
    }

    public function test_full_checkout_still_charges_the_whole_balance_when_type_is_omitted(): void
    {
        $this->fakeCheckoutCreated();
        $student = $this->studentWithBalance();

        $response = $this->actingAs($student)->post('/ledger/checkout');

        $response->assertRedirect('https://checkout.paymongo.com/cs_test_abc');
        $row = TransactionLedger::where('user_id', $student->id)->first();
        $this->assertEqualsWithDelta(3000.0, (float) $row->amount, 0.001);
    }
}
