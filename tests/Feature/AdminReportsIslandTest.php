<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\EnrollmentAgreement;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_renders_the_react_island_mount_point_with_real_data(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Report Test Student', 'major' => 'BSIT']);
        Clearance::initializeFor($student->id, '2026-2027', 1, [
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);

        $response = $this->actingAs($admin)->get('/admin/reports');

        $response->assertOk();
        $response->assertSee('id="admin-reports-root"', false);
        $response->assertSee('Report Test Student');
        $response->assertSee('&quot;cleared&quot;:1', false);
    }

    public function test_reports_page_shows_enrollment_agreement_signing_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $signed = User::factory()->create(['role' => 'applicant']);
        EnrollmentAgreement::create([
            'user_id' => $signed->id, 'envelope_id' => 'env-signed', 'status' => 'completed',
            'return_token' => 'tok-signed', 'signed_at' => now(),
        ]);

        $awaiting = User::factory()->create(['role' => 'applicant']);
        EnrollmentAgreement::create([
            'user_id' => $awaiting->id, 'envelope_id' => 'env-sent', 'status' => 'sent',
            'return_token' => 'tok-sent',
        ]);

        $declined = User::factory()->create(['role' => 'applicant']);
        EnrollmentAgreement::create([
            'user_id' => $declined->id, 'envelope_id' => 'env-declined', 'status' => 'declined',
            'return_token' => 'tok-declined',
        ]);

        $response = $this->actingAs($admin)->get('/admin/reports');

        $response->assertOk();
        $response->assertSee('&quot;agreements&quot;:{&quot;signed&quot;:1,&quot;awaiting&quot;:1,&quot;declinedOrVoided&quot;:1}', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/reports')->assertRedirect();
    }

    public function test_non_admin_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/reports')->assertForbidden();
    }
}
