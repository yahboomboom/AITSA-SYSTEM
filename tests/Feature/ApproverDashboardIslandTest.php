<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproverDashboardIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_approver_dashboard_renders_the_react_island_mount_point(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);

        $response = $this->actingAs($chair)->get('/approver/dashboard');

        $response->assertOk();
        $response->assertSee('id="approver-dashboard-root"', false);
        $response->assertDontSee('Change of Matriculation Requests');
        $response->assertDontSee('Pending Irregular Enrollments');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/approver/dashboard')->assertRedirect();
    }

    public function test_student_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/approver/dashboard')->assertForbidden();
    }

    public function test_dashboard_context_includes_clearance_states_and_enrollment_fields(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);

        $lockedStudent = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $lockedStudent->id,
            'registrar_status' => 'Pending',
            'chair_status' => 'Pending',
        ]);

        $readyStudent = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $readyStudent->id,
            'registrar_status' => 'Approved',
            'chair_status' => 'Pending',
        ]);

        $approvedStudent = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $approvedStudent->id,
            'registrar_status' => 'Approved',
            'chair_status' => 'Approved',
        ]);

        $enrollment = Enrollment::factory()->create(['type' => 'irregular', 'status' => 'pending']);
        $section = Section::factory()->create([
            'room' => 'CL-204',
            'days' => ['T', 'Th'],
            'start_time' => '10:00',
            'end_time' => '11:30',
        ]);
        $enrollment->sections()->attach($section->id);

        $response = $this->actingAs($chair)->get('/approver/dashboard');

        $response->assertOk();
        $response->assertSee('&quot;state&quot;:&quot;locked&quot;', false);
        $response->assertSee('&quot;state&quot;:&quot;ready&quot;', false);
        $response->assertSee('&quot;state&quot;:&quot;approved&quot;', false);
        $response->assertSee('&quot;room&quot;:&quot;CL-204&quot;', false);
        $response->assertSee('&quot;scheduleLabel&quot;:&quot;T\/Th 10:00\u201311:30&quot;', false);
    }
}
