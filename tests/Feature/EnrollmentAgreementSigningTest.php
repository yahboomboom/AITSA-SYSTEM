<?php

namespace Tests\Feature;

use App\Models\EnrollmentAgreement;
use App\Models\User;
use App\Services\PayMongoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Reservation checkout now gates on a native, in-app signing ceremony
 * (canvas signature + audit trail) instead of DocuSign — no student data
 * leaves the school's own database to reach a third-party e-signature vendor.
 */
class EnrollmentAgreementSigningTest extends TestCase
{
    use RefreshDatabase;

    private function baseApplicationData(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'email' => 'juan@example.com',
            'contact_number' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'sex' => 'Male',
            'address' => 'Brgy. Sala, Cabuyao, Laguna',
            'last_school' => 'Cabuyao National High School',
            'year_graduated' => '2024',
            'applicant_type' => 'NEW',
            'program_key' => 'bsoa',
            'program_name' => 'Bachelor in Science Office Administration',
            'program_level' => 'BACHELOR',
            'wants_reservation' => true,
        ], $overrides);
    }

    private function fakeSignatureDataUrl(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    public function test_reservation_checkout_redirects_to_the_native_sign_page_when_unsigned(): void
    {
        $response = $this->post('/apply', $this->baseApplicationData());
        $applicant = User::where('email', 'juan@example.com')->firstOrFail();

        $response->assertOk();
        $response->assertViewHas('checkoutUrl', URL::signedRoute('agreement.sign', ['user' => $applicant->id]));
    }

    public function test_signing_the_agreement_records_an_audit_trail_and_continues_to_checkout(): void
    {
        Storage::fake('public');

        $this->post('/apply', $this->baseApplicationData());
        $applicant = User::where('email', 'juan@example.com')->firstOrFail();

        $this->mock(PayMongoService::class, function ($mock) {
            $mock->shouldReceive('createCheckoutSession')->once()
                ->andReturn(['id' => 'cs_test_123', 'checkout_url' => 'https://checkout.paymongo.test/abc']);
        });

        $submitUrl = URL::signedRoute('agreement.sign.submit', ['user' => $applicant->id]);
        $response = $this->post($submitUrl, ['signature' => $this->fakeSignatureDataUrl()]);

        $response->assertOk();
        $response->assertViewHas('checkoutUrl', 'https://checkout.paymongo.test/abc');

        $this->assertDatabaseCount('enrollment_agreements', 1);
        $agreement = EnrollmentAgreement::first();
        $this->assertSame($applicant->id, $agreement->user_id);
        $this->assertNotNull($agreement->signed_at);
        $this->assertSame('127.0.0.1', $agreement->ip_address);
        $this->assertNotEmpty($agreement->agreement_hash);
        Storage::disk('public')->assertExists($agreement->signature_path);
    }

    public function test_signing_rejects_an_empty_signature(): void
    {
        $this->post('/apply', $this->baseApplicationData());
        $applicant = User::where('email', 'juan@example.com')->firstOrFail();

        $submitUrl = URL::signedRoute('agreement.sign.submit', ['user' => $applicant->id]);
        $response = $this->post($submitUrl, ['signature' => '']);

        $response->assertSessionHasErrors('signature');
        $this->assertDatabaseCount('enrollment_agreements', 0);
    }

    public function test_reservation_checkout_skips_straight_to_payment_once_already_signed(): void
    {
        $applicant = User::factory()->create(['role' => 'applicant']);
        EnrollmentAgreement::create([
            'user_id' => $applicant->id,
            'signature_path' => 'agreement-signatures/fake.png',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'agreement_hash' => hash('sha256', 'fake'),
            'signed_at' => now(),
        ]);

        $this->mock(PayMongoService::class, function ($mock) {
            $mock->shouldReceive('createCheckoutSession')->once()
                ->andReturn(['id' => 'cs_test_456', 'checkout_url' => 'https://checkout.paymongo.test/def']);
        });

        $url = app(\App\Services\PaymentService::class)->startReservationCheckout($applicant);

        $this->assertSame('https://checkout.paymongo.test/def', $url);
    }
}
