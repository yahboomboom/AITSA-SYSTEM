<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\EnrollmentAgreement;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Talks to the real DocuSign eSignature REST API (sandbox by default) so a
 * student's signature is an actual DocuSign-verified signing event, instead
 * of just an uploaded PNG (see SignatureController, used by staff approvers).
 *
 * Mirrors the shape of PayMongoService on purpose: same client()/verifySignature()
 * pattern, so anyone already familiar with the PayMongo integration in this
 * codebase can read this file the same way.
 *
 * Auth strategy: JWT Grant (server-to-server, no per-request user login).
 * One-time setup needed in the DocuSign sandbox account before this works:
 *   1. Create a Developer (sandbox) account at https://developers.docusign.com
 *   2. Under Apps and Keys, create an Integration Key and generate an RSA
 *      keypair for it — save the private key (PEM) somewhere safe.
 *   3. Grant consent once by visiting (replace values, then log in as the
 *      API user and click Allow):
 *      {auth_base_url}/oauth/auth?response_type=code&scope=signature%20impersonation
 *      &client_id={integration_key}&redirect_uri={any_https_url}
 *   4. Fill in DOCUSIGN_* values in .env (see config/services.php).
 */
class DocuSignService
{
    /**
     * Create + send an envelope for the student to sign, and return the
     * embedded-signing URL to redirect them to.
     *
     * @return array{envelopeId: string, signingUrl: string, agreement: EnrollmentAgreement}
     */
    public function createEnvelopeForUser(User $user, string $returnUrl): array
    {
        // A fresh random token DocuSign will hand back to us verbatim inside
        // $returnUrl — lets AgreementController recognize the student when
        // DocuSign redirects them back, without needing Laravel's signed-URL
        // middleware (DocuSign appends its own "?event=..." param, which
        // would otherwise invalidate a Laravel URL signature).
        $agreement = EnrollmentAgreement::create([
            'user_id' => $user->id,
            'status' => 'sent',
            'return_token' => Str::random(48),
        ]);

        $envelopeDefinition = config('services.docusign.template_id')
            ? $this->templateEnvelope($user)
            : $this->inlineDocumentEnvelope($user);

        try {
            $response = $this->client()->post(
                '/v2.1/accounts/' . config('services.docusign.account_id') . '/envelopes',
                $envelopeDefinition
            );
        } catch (ConnectionException) {
            throw new PaymentGatewayException('Could not reach DocuSign right now. Please try again in a moment.');
        }

        if ($response->failed()) {
            throw new PaymentGatewayException('DocuSign was unable to create the signing envelope: ' . $response->body());
        }

        $envelopeId = (string) $response->json('envelopeId');
        $agreement->update(['envelope_id' => $envelopeId]);

        $signingUrl = $this->embeddedSigningUrl($user, $envelopeId, $returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . 'token=' . $agreement->return_token);

        return ['envelopeId' => $envelopeId, 'signingUrl' => $signingUrl, 'agreement' => $agreement];
    }

    /** Ask DocuSign for an embedded (in-browser) signing ceremony URL for this recipient. */
    private function embeddedSigningUrl(User $user, string $envelopeId, string $returnUrl): string
    {
        $response = $this->client()->post(
            '/v2.1/accounts/' . config('services.docusign.account_id') . "/envelopes/{$envelopeId}/views/recipient",
            [
                'returnUrl' => $returnUrl,
                'authenticationMethod' => 'none', // JWT-authenticated app already vouches for this session; tighten if you add SSO.
                'email' => $user->email,
                'userName' => $user->name,
                // clientUserId marks this recipient as "embedded" (signs inside our
                // app) rather than "remote" (DocuSign would email them instead) —
                // must match the clientUserId used when the recipient was defined.
                'clientUserId' => (string) $user->id,
            ]
        );

        if ($response->failed()) {
            throw new PaymentGatewayException('DocuSign was unable to start the signing session: ' . $response->body());
        }

        return (string) $response->json('url');
    }

    /** Simple built-in agreement document — swap for a real template_id once one exists in DocuSign. */
    private function inlineDocumentEnvelope(User $user): array
    {
        $html = view('agreements.enrollment', ['student' => $user])->render();

        return [
            'emailSubject' => 'Please sign your AITSA Enrollment Agreement',
            'status' => 'sent',
            'documents' => [[
                'documentBase64' => base64_encode($html),
                'name' => 'AITSA Enrollment Agreement',
                'fileExtension' => 'html',
                'documentId' => '1',
            ]],
            'recipients' => [
                'signers' => [[
                    'email' => $user->email,
                    'name' => $user->name,
                    'recipientId' => '1',
                    'clientUserId' => (string) $user->id,
                    'tabs' => [
                        'signHereTabs' => [[
                            'documentId' => '1',
                            'pageNumber' => '1',
                            'xPosition' => '100',
                            'yPosition' => '600',
                        ]],
                    ],
                ]],
            ],
        ];
    }

    /** Same idea, but built from a pre-configured DocuSign template instead. */
    private function templateEnvelope(User $user): array
    {
        return [
            'templateId' => config('services.docusign.template_id'),
            'status' => 'sent',
            'templateRoles' => [[
                'email' => $user->email,
                'name' => $user->name,
                'roleName' => 'Student',
                'clientUserId' => (string) $user->id,
            ]],
        ];
    }

    /** Raw envelope status ('sent' | 'delivered' | 'completed' | 'declined' | 'voided'). */
    public function retrieveEnvelopeStatus(string $envelopeId): string
    {
        $response = $this->client()->get(
            '/v2.1/accounts/' . config('services.docusign.account_id') . "/envelopes/{$envelopeId}"
        );

        if ($response->failed()) {
            throw new PaymentGatewayException('Could not check the signing status with DocuSign.');
        }

        return (string) $response->json('status');
    }

    /**
     * Poll DocuSign directly and update our local record — used as a
     * belt-and-suspenders fallback right when the student is redirected
     * back from the signing ceremony, in case the Connect webhook (the
     * primary, reliable path) hasn't arrived yet.
     */
    public function syncStatus(EnrollmentAgreement $agreement): EnrollmentAgreement
    {
        if (! $agreement->envelope_id || $agreement->isCompleted()) {
            return $agreement;
        }

        $status = $this->retrieveEnvelopeStatus($agreement->envelope_id);

        if ($status === 'completed') {
            $agreement->update(['status' => 'completed', 'signed_at' => now()]);
        } elseif (in_array($status, ['declined', 'voided'], true)) {
            $agreement->update(['status' => $status]);
        }

        return $agreement->fresh();
    }

    /** Called by the webhook once DocuSign Connect confirms the envelope is done. */
    public function markCompletedByEnvelopeId(string $envelopeId): void
    {
        EnrollmentAgreement::where('envelope_id', $envelopeId)
            ->update(['status' => 'completed', 'signed_at' => now()]);
    }

    /** True once the student's most recent agreement has been fully signed. */
    public function hasSigned(User $user): bool
    {
        return EnrollmentAgreement::where('user_id', $user->id)
            ->latest()->first()?->isCompleted() ?? false;
    }

    /**
     * Verify a DocuSign Connect webhook request genuinely came from DocuSign
     * (HMAC signature check) — same pattern as PayMongoService::verifySignature().
     */
    public function verifySignature(string $payload, ?string $signatureHeader): bool
    {
        $secret = config('services.docusign.webhook_secret');

        if (! $secret || ! $payload || ! $signatureHeader) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($expected, $signatureHeader);
    }

    /**
     * Requests a fresh OAuth access token from DocuSign using the JWT Grant
     * flow, caching it for slightly less than its lifetime.
     */
    private function accessToken(): string
    {
        return Cache::remember('docusign_access_token', 3300, function () {
            $assertion = $this->buildJwtAssertion();

            $response = Http::asForm()->post(
                rtrim((string) config('services.docusign.auth_base_url'), '/') . '/oauth/token',
                [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]
            );

            if ($response->failed()) {
                throw new PaymentGatewayException('DocuSign authentication failed: ' . $response->body());
            }

            return (string) $response->json('access_token');
        });
    }

    /** Hand-builds and RS256-signs the JWT — no extra Composer package needed. */
    private function buildJwtAssertion(): string
    {
        $privateKey = $this->privateKey();
        $now = time();

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => config('services.docusign.integration_key'),
            'sub' => config('services.docusign.user_id'),
            'aud' => parse_url((string) config('services.docusign.auth_base_url'), PHP_URL_HOST),
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'signature impersonation',
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($payload)),
        ];

        $signingInput = implode('.', $segments);
        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function privateKey(): string
    {
        if ($b64 = config('services.docusign.private_key_b64')) {
            return base64_decode($b64);
        }

        $path = config('services.docusign.private_key_path');
        if ($path && is_readable($path)) {
            return (string) file_get_contents($path);
        }

        throw new PaymentGatewayException('DocuSign is not configured (missing private key). Signature is unavailable right now.');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function client(): PendingRequest
    {
        if (! config('services.docusign.integration_key')) {
            throw new PaymentGatewayException('DocuSign is not configured. Please use the uploaded-signature flow instead.');
        }

        return Http::baseUrl((string) config('services.docusign.api_base_url'))
            ->withToken($this->accessToken())
            ->acceptJson()
            ->timeout(15);
    }
}