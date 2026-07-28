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
        $response->assertSee('&quot;declineUrl&quot;', false);
        $response->assertSee('&quot;applicantType&quot;:&quot;NEW&quot;', false);
        $response->assertSee('&quot;isApproved&quot;:false', false);
        $response->assertSee('&quot;studentName&quot;:&quot;' . $student->name . '&quot;', false);
    }
}
