<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PayMongoService
{
    /**
     * @param  string|null  $successUrl  Overrides the default student-ledger return URL.
     *                                   Used by flows where the payer isn't an authenticated
     *                                   student yet (e.g. an applicant paying the reservation fee).
     * @param  string|null  $cancelUrl   Overrides the default student-ledger cancel URL.
     * @return array{id: string, checkout_url: string}
     */
    public function createCheckoutSession(User $user, int $amountCentavos, string $description, ?string $successUrl = null, ?string $cancelUrl = null): array
    {
        try {
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
                        // Use the caller-provided URL first (e.g. applicant reservation flow),
                        // otherwise fall back to the .env config, otherwise the default student ledger route.
                        'success_url' => $successUrl ?? config('services.paymongo.success_url') ?: route('ledger.payment.return'),/**success */
                        'cancel_url' => $cancelUrl ?? config('services.paymongo.cancel_url') ?: route('ledger.payment.cancel'),
                        'metadata' => ['user_id' => (string) $user->id, 'login_id' => (string) ($user->login_id ?? '')],
                    ],
                ],
            ]);
        } catch (ConnectionException) {
            throw new PaymentGatewayException('Payment gateway is unavailable — please try again or pay at the cashier window.');
        }

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
        try {
            $response = $this->client()->get('/checkout_sessions/' . $id);
        } catch (ConnectionException) {
            throw new PaymentGatewayException('Could not reach the payment gateway to verify. Please try again.');
        }

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

    /**
     * Verify that this webhook request genuinely came from PayMongo and
     * wasn't spoofed. Uses the HMAC signature in the "Paymongo-Signature"
     * header, compared against our own webhook secret.
     */
    public function verifySignature(string $payload, ?string $signatureHeader): bool
    {
        // If we don't have a webhook secret configured, we can't verify the signature.
        if (! config('services.paymongo.webhook_secret')) {
            return false;
        }

        // If the payload is empty, it's definitely not from PayMongo.
        if (! $payload) {
            return false;
        }
        // No signature header at all = definitely not from PayMongo.
        if (! $signatureHeader) {
        return false;
        }
   
        // The header format is: "t=timestamp,te=test_sig,li=live_sig"
       // Parse it into a key-value array.
       $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
        if (str_contains($part, '=')) {
            [$key, $value] = explode('=', $part, 2);
            $parts[$key] = $value;
        }
    }

        $timestamp = $parts['t'] ?? null;
        $secret = config('services.paymongo.webhook_secret');

        // Pick the test or live signature depending on which secret key we're using.
        $isLive = str_starts_with((string) config('services.paymongo.secret'), 'sk_live_');
        $signature = $isLive ? ($parts['li'] ?? null) : ($parts['te'] ?? null);

        if (! $timestamp || ! $signature || ! $secret) {
            return false;
            }

         // Recreate the expected signature using our own secret, then compare it
         // against the one PayMongo sent — if they match, the request is genuine.
         $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

            return hash_equals($expected, $signature);
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