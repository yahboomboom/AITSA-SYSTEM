<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocuSignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocuSignWebhookController extends Controller
{
    /**
     * Receives DocuSign Connect's "envelope status changed" events. This is
     * the PRIMARY, reliable path for finding out a student signed — it
     * fires even if the student closes their browser right after signing,
     * before the app-side redirect (AgreementController) gets a chance to run.
     */
    public function handle(Request $request, DocuSignService $docusign)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-DocuSign-Signature-1');

        // Step 1: verify this request genuinely came from DocuSign Connect.
        if (! $docusign->verifySignature($payload, $signature)) {
            Log::warning('DocuSign webhook: invalid signature.');
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        // DocuSign's "Envelope has a Recipient Event" HMAC payload shape —
        // adjust the two lines below to match whichever Connect template
        // (Legacy XML vs Aggregate JSON) you pick when creating the Connect
        // configuration in the DocuSign sandbox admin console.
        $envelopeId = $event['data']['envelopeId'] ?? $event['envelopeId'] ?? null;
        $status = $event['data']['envelopeSummary']['status'] ?? $event['status'] ?? null;
        $eventId = $event['generatedDateTime'] ?? $envelopeId . '-' . $status; // fall back to a stable-ish composite key

        if (! $envelopeId) {
            return response()->json(['message' => 'Malformed payload'], 400);
        }

        // Step 2: idempotency guard, same pattern as the PayMongo webhook —
        // DocuSign Connect retries if it doesn't get a fast 200 back.
        $inserted = DB::table('docusign_webhook_events')->insertOrIgnore([
            'event_id' => (string) $eventId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $inserted) {
            return response()->json(['message' => 'Already processed'], 200);
        }

        // Step 3: only "completed" means the student actually finished signing.
        if ($status === 'completed') {
            $docusign->markCompletedByEnvelopeId($envelopeId);
            Log::info('DocuSign webhook processed', ['envelope' => $envelopeId, 'status' => $status]);
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }
}