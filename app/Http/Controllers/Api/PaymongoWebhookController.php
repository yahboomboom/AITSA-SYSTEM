<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PayMongoService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymongoWebhookController extends Controller
{
    public function handle(Request $request, PayMongoService $gateway, PaymentService $payments)
 {
    $payload = $request->getContent();
    $signature = $request->header('Paymongo-Signature');

    // Step 1: Verify this request genuinely came from PayMongo.
    if (! $gateway->verifySignature($payload, $signature)) {
        Log::warning('PayMongo webhook: invalid signature.');
        return response()->json(['message' => 'Invalid signature'], 400);
    }

    $event = json_decode($payload, true);
    $eventId = $event['data']['id'] ?? null;
    $eventType = $event['data']['attributes']['type'] ?? null;

    if (! $eventId) {
        return response()->json(['message' => 'Malformed payload'], 400);
    }

    // Step 2: Idempotency guard — if we've already processed this exact
    // event before (PayMongo retries), don't process it again.
    $inserted = DB::table('paymongo_webhook_events')->insertOrIgnore([
        'event_id' => $eventId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    if (! $inserted) {
        return response()->json(['message' => 'Already processed'], 200);
    }

    // Step 3: If this is a "payment successful" event, settle the
    // matching TransactionLedger row.
    if ($eventType === 'checkout_session.payment.paid') {
        $checkoutSessionId = $event['data']['attributes']['data']['id'] ?? null;

        if ($checkoutSessionId) {
            $result = $payments->settleByCheckoutSessionId($checkoutSessionId);
            Log::info('PayMongo webhook processed', ['session' => $checkoutSessionId, 'result' => $result]);
        }
    }

    // Always respond 200 — even for "already processed" or "unrecognized
    // event" cases — so PayMongo doesn't keep retrying (it retries
    // whenever it doesn't get a 200 response).
    return response()->json(['message' => 'Webhook received'], 200);
  }
}
