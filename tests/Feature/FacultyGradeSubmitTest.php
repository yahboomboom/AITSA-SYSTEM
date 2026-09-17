<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\GradeSubmission;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyGradeSubmitTest extends TestCase
{
    use RefreshDatabase;

    private function enrollStudentInSection(User $student, Section $section): void
    {
        $enrollment = Enrollment::factory()->create(['user_id' => $student->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach($section->id);
    }

    public function test_faculty_can_submit_once_every_enrolled_student_has_a_grade(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $student = User::factory()->create(['role' => 'student']);
        $this->enrollStudentInSection($student, $section);

        $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades", [
            'grades' => [$student->id => '88'],
        ]);

        $response = $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades/submit");

        $response->assertRedirect();
        $submission = GradeSubmission::where('section_id', $section->id)->first();
        $this->assertSame('pending_chair', $submission->status);
        $this->assertNotNull($submission->submitted_at);
    }

    public function test_faculty_cannot_submit_when_a_student_is_missing_a_grade(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $graded = User::factory()->create(['role' => 'student']);
        $ungraded = User::factory()->create(['role' => 'student']);
        $this->enrollStudentInSection($graded, $section);
        $this->enrollStudentInSection($ungraded, $section);

        $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades", [
            'grades' => [$graded->id => '88'],
        ]);

        $response = $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades/submit");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('draft', GradeSubmission::where('section_id', $section->id)->first()->status);
    }

    public function test_the_missing_grade_error_actually_renders_on_the_grade_entry_page(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $graded = User::factory()->create(['role' => 'student']);
        $ungraded = User::factory()->create(['role' => 'student']);
        $this->enrollStudentInSection($graded, $section);
        $this->enrollStudentInSection($ungraded, $section);

        $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades", [
            'grades' => [$graded->id => '88'],
        ]);

        $response = $this->actingAs($faculty)
            ->from("/faculty/sections/{$section->id}/grades")
            ->followingRedirects()
            ->post("/faculty/sections/{$section->id}/grades/submit");

        $response->assertOk();
        $response->assertSee('Enter a grade for every enrolled student before submitting.');
    }

    public function test_faculty_cannot_submit_a_section_they_do_not_teach(): void
    {
        $owner = User::factory()->create(['role' => 'faculty']);
        $intruder = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $owner->id]);

        $response = $this->actingAs($intruder)->post("/faculty/sections/{$section->id}/grades/submit");

        $response->assertForbidden();
    }

    public function test_faculty_cannot_resubmit_a_submission_already_pending(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair']);

        $response = $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades/submit");

        $response->assertForbidden();
    }

    public function test_faculty_cannot_submit_a_section_with_no_enrolled_students(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'draft']);

        $response = $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades/submit");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('draft', GradeSubmission::where('section_id', $section->id)->first()->status);
    }

    public function test_submitting_refreshes_faculty_id_to_the_sections_current_instructor(): void
    {
        $oldFaculty = User::factory()->create(['role' => 'faculty']);
        $newFaculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $oldFaculty->id]);
        $student = User::factory()->create(['role' => 'student']);
        $this->enrollStudentInSection($student, $section);

        $submission = GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $oldFaculty->id, 'status' => 'draft']);
        \App\Models\GradeSubmissionItem::create(['grade_submission_id' => $submission->id, 'user_id' => $student->id, 'final_grade' => '88', 'status' => 'Passed']);

        // Chair reassigns the section to a new instructor before the submit happens.
        $section->update(['faculty_id' => $newFaculty->id]);

        $response = $this->actingAs($newFaculty)->post("/faculty/sections/{$section->id}/grades/submit");

        $response->assertRedirect();
        $submission->refresh();
        $this->assertSame($newFaculty->id, $submission->faculty_id);
    }

    public function test_resubmitting_after_a_rejection_clears_stale_chair_approval_fields(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $chair = User::factory()->create(['role' => 'chair']);
        $registrar = User::factory()->create(['role' => 'registrar']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $student = User::factory()->create(['role' => 'student']);
        $this->enrollStudentInSection($student, $section);

        $submission = GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair', 'submitted_at' => now()]);
        \App\Models\GradeSubmissionItem::create(['grade_submission_id' => $submission->id, 'user_id' => $student->id, 'final_grade' => '88', 'status' => 'Passed']);

        $this->actingAs($chair)->post("/approver/grades/{$submission->id}/approve");
        $submission->refresh();
        $this->assertSame('pending_registrar', $submission->status);
        $this->assertNotNull($submission->chair_id);
        $this->assertNotNull($submission->chair_at);

        $this->actingAs($registrar)->post("/registrar/grades/{$submission->id}/reject", ['remarks' => 'Please recheck.']);
        $submission->refresh();
        $this->assertSame('draft', $submission->status);

        $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades/submit");

        $submission->refresh();
        $this->assertSame('pending_chair', $submission->status);
        $this->assertNull($submission->chair_id);
        $this->assertNull($submission->chair_at);
    }
}
