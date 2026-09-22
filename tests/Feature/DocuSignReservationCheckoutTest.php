<?php

namespace Tests\Feature;

use App\Services\DocuSignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PaymentService::startReservationCheckout() references $this->docusign but
 * DocuSignService was never injected into PaymentService's constructor —
 * with DOCUSIGN_ENABLED off this was silent, but the first real reservation
 * attempt after turning it on would fatal-error with "Call to a member
 * function hasSigned() on null". This locks in the fix.
 */
class DocuSignReservationCheckoutTest extends TestCase
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

    public function test_reservation_checkout_redirects_to_docusign_when_enabled_and_unsigned(): void
    {
        config(['services.docusign.enabled' => true]);

        $this->mock(DocuSignService::class, function ($mock) {
            $mock->shouldReceive('hasSigned')->once()->andReturn(false);
            $mock->shouldReceive('createEnvelopeForUser')->once()
                ->andReturn(['signingUrl' => 'https://demo.docusign.net/fake-signing-url']);
        });

        $response = $this->post('/apply', $this->baseApplicationData());

        $response->assertOk();
        $response->assertViewHas('checkoutUrl', 'https://demo.docusign.net/fake-signing-url');
    }
}
