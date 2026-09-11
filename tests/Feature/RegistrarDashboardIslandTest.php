<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarDashboardIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_dashboard_renders_the_react_island_mount_point(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee('id="registrar-dashboard-root"', false);
        $response->assertDontSee('Student Document Submissions');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/registrar/dashboard')->assertRedirect();
    }

    public function test_student_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/registrar/dashboard')->assertForbidden();
    }

    public function test_dashboard_context_includes_applicant_and_clearance_rows(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create(['role' => 'applicant', 'applicant_type' => 'NEW']);
        $student   = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create([
            'user_id' => $student->id,
            'admission_status' => 'Approved',
            'chair_status' => 'Pending',
            'cashier_status' => 'Pending',
            'registrar_status' => 'Pending',
        ]);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertDontSee('&quot;declineUrl&quot;', false);
        $response->assertDontSee('&quot;verifyUrl&quot;', false);
        $response->assertSee('&quot;applicantType&quot;:&quot;NEW&quot;', false);
        $response->assertSee('&quot;isApproved&quot;:false', false);
        $response->assertSee('&quot;studentName&quot;:&quot;' . $student->name . '&quot;', false);
    }

    public function test_dashboard_context_includes_a_direct_activation_url_per_applicant(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create(['role' => 'applicant']);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee('&quot;activateApplicantUrl&quot;:&quot;' . str_replace('/', '\/', route('registrar.activate-applicant', $applicant->id)) . '&quot;', false);
    }

    public function test_dashboard_does_not_list_a_students_clearance_from_a_past_term(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student']);

        \App\Models\Setting::put('school_year', '2026-2027');
        \App\Models\Setting::put('semester', '1');
        \App\Models\Setting::clearCache();
        \App\Models\Clearance::initializeFor($student->id, '2026-2027', 1);

        \App\Models\Setting::put('semester', '2');
        \App\Models\Setting::clearCache();
        \App\Models\Clearance::initializeFor($student->id, '2026-2027', 2);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        // The student's name should appear exactly once in the clearances
        // list, not twice (once per term) — count occurrences inside the
        // JSON blob the island receives, not anywhere else on the page
        // (the sidebar/header may legitimately repeat the logged-in
        // registrar's own name).
        $json = $response->getContent();
        $occurrences = substr_count($json, '&quot;studentName&quot;:&quot;' . $student->name . '&quot;');
        $this->assertSame(1, $occurrences);
    }

    public function test_dashboard_context_includes_provisional_fields_and_grant_url(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Pending',
        ]);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee('&quot;isProvisional&quot;:false', false);
        $response->assertSee('&quot;grantProvisionalUrl&quot;:&quot;' . str_replace('/', '\/', route('registrar.grant-provisional', $clearance->id)) . '&quot;', false);
    }

    public function test_dashboard_context_reflects_a_granted_provisional_clearance(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Pending',
            'is_provisional' => true,
        ]);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertSee('&quot;isProvisional&quot;:true', false);
    }
}
