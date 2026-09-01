<?php

namespace Tests\Feature;

use App\Models\Enrollment;
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

    public function test_faculty_cannot_view_grade_entry_for_a_section_they_do_not_teach(): void
    {
        $owner = User::factory()->create(['role' => 'faculty']);
        $intruder = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $owner->id]);

        $response = $this->actingAs($intruder)->get("/faculty/sections/{$section->id}/grades");

        $response->assertForbidden();
    }

    public function test_faculty_can_submit_grades_for_enrolled_students(): void
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
        $this->assertSame('Passed', StudentGrade::where('user_id', $passing->id)->where('subject_code', 'CC101')->first()->status);
        $this->assertSame('Failed', StudentGrade::where('user_id', $failing->id)->where('subject_code', 'CC101')->first()->status);
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
        $this->assertDatabaseMissing('student_grades', ['user_id' => $student->id]);
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

        $this->assertDatabaseMissing('student_grades', ['user_id' => $outsider->id]);
    }
}
