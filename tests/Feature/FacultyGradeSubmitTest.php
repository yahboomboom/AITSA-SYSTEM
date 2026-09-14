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
}
