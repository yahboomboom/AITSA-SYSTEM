<?php

namespace Tests\Feature;

use App\Models\TransactionLedger;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * PayMongo's webhook (server-to-server) can settle a reservation payment and
 * auto-activate the applicant into a student account before the applicant's
 * own browser finishes redirecting back to /apply/reservation/{user}/return.
 * The return route must not 404 just because the role changed out from under
 * it — the payment already succeeded and the applicant should see the receipt.
 */
class ReservationReturnWebhookRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_route_shows_receipt_even_when_webhook_settled_first(): void
    {
        Mail::fake();

        $applicant = User::factory()->create(['role' => 'applicant', 'is_reserved' => false]);
        TransactionLedger::create([
            'user_id' => $applicant->id,
            'gateway' => 'paymongo',
            'checkout_session_id' => 'cs_test_race',
            'amount' => 500,
            'fee_type' => 'reservation',
            'status' => 'Pending',
            'reference_no' => 'RES-RACE1',
        ]);

        // Simulate the webhook winning the race: it settles the payment and
        // flips the applicant's role to 'student' before the browser returns.
        $result = app(PaymentService::class)->settleByCheckoutSessionId('cs_test_race');
        $this->assertTrue($result['ok']);
        $this->assertSame('student', $applicant->fresh()->role);

        $returnUrl = URL::signedRoute('apply.reservation.return', ['user' => $applicant->id]);

        $response = $this->get($returnUrl);

        $response->assertRedirect(route('apply'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('receipt');
        $response->assertSessionMissing('error');
    }
}
