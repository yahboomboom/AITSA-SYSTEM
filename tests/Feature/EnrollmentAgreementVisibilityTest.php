<?php

namespace Tests\Feature;

use App\Models\EnrollmentAgreement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnrollmentAgreementVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function signedAgreementFor(User $user): EnrollmentAgreement
    {
        return EnrollmentAgreement::create([
            'user_id' => $user->id,
            'signature_path' => 'agreement-signatures/fake.png',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'agreement_hash' => hash('sha256', 'fake-agreement'),
            'signed_at' => now(),
        ]);
    }

    public function test_registrar_dashboard_shows_signed_status_for_a_signed_applicant(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $this->signedAgreementFor($applicant);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee('&quot;agreementSigned&quot;:true', false);
    }

    public function test_registrar_dashboard_shows_unsigned_for_an_applicant_without_agreement(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        User::factory()->create(['role' => 'applicant']);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee('&quot;agreementSigned&quot;:false', false);
    }

    public function test_clearance_page_shows_signed_banner_for_a_signed_student(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->signedAgreementFor($student);

        $response = $this->actingAs($student)->get('/clearance');

        $response->assertOk();
        $response->assertSee('Enrollment Agreement Signed');
    }

    public function test_clearance_page_hides_banner_when_student_never_signed(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/clearance');

        $response->assertOk();
        $response->assertDontSee('Enrollment Agreement Signed');
    }

    public function test_student_can_download_their_own_signed_agreement(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('agreement-signatures/fake.png', 'fake-bytes');

        $student = User::factory()->create(['role' => 'student']);
        $this->signedAgreementFor($student);

        $response = $this->actingAs($student)->get('/my-agreement');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_downloading_with_no_signed_agreement_redirects_with_an_error(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/my-agreement');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_registrar_can_download_an_applicants_signed_agreement(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('agreement-signatures/fake.png', 'fake-bytes');

        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $this->signedAgreementFor($applicant);

        $response = $this->actingAs($registrar)->get("/registrar/applicants/{$applicant->id}/agreement");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_non_staff_cannot_download_another_users_agreement(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $applicant = User::factory()->create(['role' => 'applicant']);

        $response = $this->actingAs($student)->get("/registrar/applicants/{$applicant->id}/agreement");

        $response->assertForbidden();
    }
}
