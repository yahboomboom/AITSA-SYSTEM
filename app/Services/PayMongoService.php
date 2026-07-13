<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PayMongoService
{
    /** @return array{id: string, checkout_url: string} */
    public function createCheckoutSession(User $user, int $amountCentavos, string $description): array
    {
        $response = $this->client()->post('/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'line_items' => [[
                        'name' => $description,
                        'amount' => $amountCentavos,
                        'currency' => 'PHP',
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => ['gcash', 'card', 'paymaya'],
                    'description' => $description,
                    'success_url' => route('ledger.payment.return'),
                    'cancel_url' => route('ledger.payment.cancel'),
                    'metadata' => ['user_id' => (string) $user->id, 'login_id' => (string) ($user->login_id ?? '')],
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Payment gateway is unavailable — please try again or pay at the cashier window.');
        }

        return [
            'id' => (string) $response->json('data.id'),
            'checkout_url' => (string) $response->json('data.attributes.checkout_url'),
        ];
    }

    /** Raw `data` object of the checkout session. */
    public function retrieveCheckoutSession(string $id): array
    {
        $response = $this->client()->get('/checkout_sessions/' . $id);

        if ($response->failed()) {
            throw new PaymentGatewayException('Could not reach the payment gateway to verify. Please try again.');
        }

        return (array) $response->json('data');
    }

    public function sessionIsPaid(array $session): bool
    {
        foreach ($session['attributes']['payments'] ?? [] as $payment) {
            if (($payment['attributes']['status'] ?? null) === 'paid') {
                return true;
            }
        }

        return false;
    }

    private function client(): PendingRequest
    {
        $secret = config('services.paymongo.secret');
        if (! $secret) {
            throw new PaymentGatewayException('Payment gateway is not configured. Please pay at the cashier window.');
        }

        return Http::baseUrl((string) config('services.paymongo.base_url'))
            ->withBasicAuth($secret, '')
            ->acceptJson()
            ->timeout(15);
    }
}
