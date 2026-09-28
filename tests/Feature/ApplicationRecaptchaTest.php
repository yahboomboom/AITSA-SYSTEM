<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApplicationRecaptchaTest extends TestCase
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
            'program_key' => 'em3',
            'program_name' => 'Events Management NC III',
            'program_level' => 'TESDA',
            'g-recaptcha-response' => 'test-token',
        ], $overrides);
    }

    public function test_application_is_rejected_when_the_captcha_token_is_missing(): void
    {
        $data = $this->baseApplicationData();
        unset($data['g-recaptcha-response']);

        $response = $this->post('/apply', $data);

        $response->assertSessionHasErrors('g-recaptcha-response');
        $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);
    }

    public function test_application_is_rejected_when_google_reports_the_captcha_failed(): void
    {
        Http::fake([
            'www.google.com/recaptcha/*' => Http::response(['success' => false]),
        ]);

        $response = $this->post('/apply', $this->baseApplicationData());

        $response->assertSessionHasErrors('g-recaptcha-response');
        $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);
    }

    public function test_application_succeeds_when_google_confirms_the_captcha(): void
    {
        Http::fake([
            'www.google.com/recaptcha/*' => Http::response(['success' => true]),
        ]);

        $response = $this->post('/apply', $this->baseApplicationData());

        $response->assertSessionDoesntHaveErrors('g-recaptcha-response');
        $this->assertDatabaseHas('users', ['email' => 'juan@example.com']);
    }

    public function test_the_actual_token_is_forwarded_to_googles_verify_endpoint(): void
    {
        Http::fake([
            'www.google.com/recaptcha/*' => Http::response(['success' => true]),
        ]);

        $this->post('/apply', $this->baseApplicationData(['g-recaptcha-response' => 'a-specific-token']));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
                && $request['response'] === 'a-specific-token'
                && $request['secret'] === config('services.recaptcha.secret_key');
        });
    }
}
