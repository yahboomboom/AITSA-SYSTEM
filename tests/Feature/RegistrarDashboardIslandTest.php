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

    public function test_registrar_dashboard_lists_pending_grade_submissions(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $faculty = User::factory()->create(['role' => 'faculty', 'name' => 'Prof. Dizon']);
        $subject = Subject::factory()->create(['code' => 'CC102']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id, 'block_label' => '2B']);
        GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_registrar']);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee('CC102');
        $response->assertSee('Prof. Dizon');
    }

    public function test_dashboard_context_includes_staged_grade_items_for_drilldown(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC102']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Pedro Reyes']);
        $submission = GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_registrar']);
        GradeSubmissionItem::create(['grade_submission_id' => $submission->id, 'user_id' => $student->id, 'final_grade' => '85', 'status' => 'Passed']);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee('&quot;name&quot;:&quot;Pedro Reyes&quot;', false);
        $response->assertSee('&quot;grade&quot;:&quot;85&quot;', false);
    }

    public function test_dashboard_does_not_crash_when_a_graded_students_account_is_soft_deleted(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC102']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $student = User::factory()->create(['role' => 'student']);
        $submission = GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_registrar']);
        GradeSubmissionItem::create(['grade_submission_id' => $submission->id, 'user_id' => $student->id, 'final_grade' => '80', 'status' => 'Passed']);
        $student->delete();

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee('Unknown (deleted account)');
    }

    public function test_dashboard_shows_the_sections_current_faculty_after_reassignment(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $originalFaculty = User::factory()->create(['role' => 'faculty', 'name' => 'Prof. Original']);
        $newFaculty = User::factory()->create(['role' => 'faculty', 'name' => 'Prof. Replacement']);
        $subject = Subject::factory()->create(['code' => 'CC102']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $originalFaculty->id]);
        GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $originalFaculty->id, 'status' => 'pending_registrar']);
        $section->update(['faculty_id' => $newFaculty->id]);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee('Prof. Replacement');
        $response->assertDontSee('Prof. Original');
    }

    public function test_dashboard_flags_a_student_enrolled_after_the_submission_was_graded(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC102']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $gradedStudent = User::factory()->create(['role' => 'student']);
        $submission = GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_registrar']);
        GradeSubmissionItem::create(['grade_submission_id' => $submission->id, 'user_id' => $gradedStudent->id, 'final_grade' => '85', 'status' => 'Passed']);

        $lateStudent = User::factory()->create(['role' => 'student']);
        $enrollment = Enrollment::factory()->create(['user_id' => $lateStudent->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach($section->id);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee('&quot;missingGrades&quot;:1', false);
    }
}
