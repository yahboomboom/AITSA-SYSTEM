<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\GradeSubmission;
use App\Models\Section;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeApprovalVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function enrollStudentInSection(User $student, Section $section): void
    {
        $enrollment = Enrollment::factory()->create(['user_id' => $student->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach($section->id);
    }

    public function test_a_failing_grade_stays_invisible_until_registrar_approval_then_appears_everywhere(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $chair = User::factory()->create(['role' => 'chair']);
        $registrar = User::factory()->create(['role' => 'registrar']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSIT']);
        $this->enrollStudentInSection($student, $section);

        // Faculty enters a failing grade and submits.
        $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades", ['grades' => [$student->id => '60']]);
        $this->actingAs($faculty)->post("/faculty/sections/{$section->id}/grades/submit");

        // Still pending_chair: invisible everywhere that reads student_grades.
        $this->assertDatabaseMissing('student_grades', ['user_id' => $student->id]);
        $studentGradesPage = $this->actingAs($student)->get('/grades');
        $studentGradesPage->assertDontSee('CC101');
        $registrarSearch = $this->actingAs($registrar)->withSession(['auth.password_confirmed_at' => time()])->get('/registrar/students/search?status=all');
        $registrarSearch->assertJsonMissing(['isIrregular' => true]);

        // Chair approves -> pending_registrar: still invisible.
        $submission = GradeSubmission::where('section_id', $section->id)->first();
        $this->actingAs($chair)->post("/approver/grades/{$submission->id}/approve");
        $this->assertDatabaseMissing('student_grades', ['user_id' => $student->id]);

        // Registrar approves -> now visible and correct everywhere.
        $this->actingAs($registrar)->post("/registrar/grades/{$submission->id}/approve");

        $this->assertSame('Failed', StudentGrade::where('user_id', $student->id)->where('subject_code', 'CC101')->first()->status);

        $studentGradesPageAfter = $this->actingAs($student)->get('/grades');
        $studentGradesPageAfter->assertSee('CC101');

        $registrarSearchAfter = $this->actingAs($registrar)->withSession(['auth.password_confirmed_at' => time()])->get('/registrar/students/search?status=all');
        $rows = $registrarSearchAfter->json('rows');
        $studentRow = collect($rows)->firstWhere('id', $student->id);
        $this->assertNotNull($studentRow);
        $this->assertTrue($studentRow['isIrregular']);
    }
}
