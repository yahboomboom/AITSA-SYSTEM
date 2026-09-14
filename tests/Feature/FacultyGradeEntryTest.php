<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\GradeSubmission;
use App\Models\GradeSubmissionItem;
use App\Models\Section;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyGradeEntryTest extends TestCase
{
    use RefreshDatabase;

    private function enrollStudentInSection(User $student, Section $section): void
    {
        $enrollment = Enrollment::factory()->create(['user_id' => $student->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach($section->id);
    }

    public function test_faculty_can_view_grade_entry_for_a_section_they_teach(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Juan Dela Cruz']);
        $this->enrollStudentInSection($student, $section);

        $response = $this->actingAs($faculty)->get("/faculty/sections/{$section->id}/grades");

        $response->assertOk();
        $response->assertSee('Juan Dela Cruz');
    }

    public function test_viewing_grade_entry_creates_a_draft_submission_for_the_section(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($faculty)->get("/faculty/sections/{$section->id}/grades");

        $this->assertDatabaseHas('grade_submissions', [
            'section_id' => $section->id,
            'faculty_id' => $faculty->id,
            'status' => 'draft',
        ]);
    }

    public function test_faculty_cannot_view_grade_entry_for_a_section_they_do_not_teach(): void
    {
        $owner = User::factory()->create(['role' => 'faculty']);
        $intruder = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $owner->id]);

        $response = $this->actingAs($intruder)->get("/faculty/sections/{$section->id}/grades");

        $response->assertForbidden();
    }

    public function test_faculty_can_save_draft_grades_and_they_land_in_the_staging_table(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $passing = User::factory()->create(['role' => 'student']);
        $failing = User::factory()->create(['role' => 'student']);
        $this->enrollStudentInSection($passing, $section);
        $this->enrollStudentInSection($failing, $section);

        $response = $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades", [
            'grades' => [
                $passing->id => '88',
                $failing->id => '60',
            ],
        ]);

        $response->assertRedirect();
        $submission = GradeSubmission::where('section_id', $section->id)->first();
        $this->assertSame('draft', $submission->status);
        $this->assertSame('Passed', GradeSubmissionItem::where('grade_submission_id', $submission->id)->where('user_id', $passing->id)->first()->status);
        $this->assertSame('Failed', GradeSubmissionItem::where('grade_submission_id', $submission->id)->where('user_id', $failing->id)->first()->status);
        $this->assertDatabaseMissing('student_grades', ['user_id' => $passing->id]);
        $this->assertDatabaseMissing('student_grades', ['user_id' => $failing->id]);
    }

    public function test_faculty_cannot_save_grades_once_submission_left_draft(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $student = User::factory()->create(['role' => 'student']);
        $this->enrollStudentInSection($student, $section);
        GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair']);

        $response = $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades", [
            'grades' => [$student->id => '90'],
        ]);

        $response->assertForbidden();
    }

    public function test_faculty_cannot_submit_grades_for_a_section_they_do_not_teach(): void
    {
        $owner = User::factory()->create(['role' => 'faculty']);
        $intruder = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $owner->id]);
        $student = User::factory()->create(['role' => 'student']);
        $this->enrollStudentInSection($student, $section);

        $response = $this->actingAs($intruder)->post("/faculty/sections/{$section->id}/grades", [
            'grades' => [$student->id => '90'],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('grade_submission_items', ['user_id' => $student->id]);
    }

    public function test_faculty_cannot_grade_a_student_outside_the_section(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $outsider = User::factory()->create(['role' => 'student']);

        $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades", [
            'grades' => [$outsider->id => '95'],
        ]);

        $this->assertDatabaseMissing('grade_submission_items', ['user_id' => $outsider->id]);
    }

    public function test_grade_entry_page_shows_a_status_banner_and_submit_url_when_draft(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);

        $response = $this->actingAs($faculty)->get("/faculty/sections/{$section->id}/grades");

        $response->assertOk();
        $response->assertSee(route('faculty.sections.grades.submit', $section->id), false);
        $response->assertSee('&quot;submissionStatus&quot;:&quot;draft&quot;', false);
    }

    public function test_grade_entry_page_shows_rejection_remarks(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        GradeSubmission::create([
            'section_id' => $section->id,
            'faculty_id' => $faculty->id,
            'status' => 'draft',
            'rejected_by' => 'chair',
            'remarks' => 'Please double check row 4.',
        ]);

        $response = $this->actingAs($faculty)->get("/faculty/sections/{$section->id}/grades");

        $response->assertOk();
        $response->assertSee('Please double check row 4.');
    }
}
