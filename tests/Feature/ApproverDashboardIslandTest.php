<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\GradeSubmission;
use App\Models\GradeSubmissionItem;
use App\Models\Section;
use App\Models\Subject;
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

    public function test_dashboard_does_not_list_a_students_clearance_from_a_past_term(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $student = User::factory()->create(['role' => 'student']);

        \App\Models\Setting::put('school_year', '2026-2027');
        \App\Models\Setting::put('semester', '1');
        \App\Models\Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 1);

        \App\Models\Setting::put('semester', '2');
        \App\Models\Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 2);

        $response = $this->actingAs($chair)->get('/approver/dashboard');

        // The student's name should appear exactly once in the clearances
        // list, not twice (once per term).
        $json = $response->getContent();
        $occurrences = substr_count($json, '&quot;studentName&quot;:&quot;' . $student->name . '&quot;');
        $this->assertSame(1, $occurrences);
    }

    public function test_dashboard_lists_pending_grade_submissions(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $faculty = User::factory()->create(['role' => 'faculty', 'name' => 'Prof. Ramos']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id, 'block_label' => '1A']);
        GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair', 'submitted_at' => now()]);

        $response = $this->actingAs($chair)->get('/approver/dashboard');

        $response->assertOk();
        $response->assertSee('CC101');
        $response->assertSee('Prof. Ramos');
    }

    public function test_dashboard_context_includes_staged_grade_items_for_drilldown(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Maria Santos']);
        $submission = GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair', 'submitted_at' => now()]);
        GradeSubmissionItem::create(['grade_submission_id' => $submission->id, 'user_id' => $student->id, 'final_grade' => '91', 'status' => 'Passed']);

        $response = $this->actingAs($chair)->get('/approver/dashboard');

        $response->assertOk();
        $response->assertSee('&quot;name&quot;:&quot;Maria Santos&quot;', false);
        $response->assertSee('&quot;grade&quot;:&quot;91&quot;', false);
    }
}
