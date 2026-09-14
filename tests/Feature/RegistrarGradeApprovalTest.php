<?php

namespace Tests\Feature;

use App\Models\GradeSubmission;
use App\Models\GradeSubmissionItem;
use App\Models\Section;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarGradeApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_approval_finalizes_grades_into_student_grades(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $passing = User::factory()->create(['role' => 'student']);
        $failing = User::factory()->create(['role' => 'student']);
        $submission = GradeSubmission::create([
            'section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_registrar',
        ]);
        GradeSubmissionItem::create(['grade_submission_id' => $submission->id, 'user_id' => $passing->id, 'final_grade' => '88', 'status' => 'Passed']);
        GradeSubmissionItem::create(['grade_submission_id' => $submission->id, 'user_id' => $failing->id, 'final_grade' => '60', 'status' => 'Failed']);

        $response = $this->actingAs($registrar)->post("/registrar/grades/{$submission->id}/approve");

        $response->assertRedirect();
        $submission->refresh();
        $this->assertSame('approved', $submission->status);
        $this->assertSame($registrar->id, $submission->registrar_id);
        $this->assertNotNull($submission->registrar_at);

        $this->assertSame('Passed', StudentGrade::where('user_id', $passing->id)->where('subject_code', 'CC101')->first()->status);
        $this->assertSame('88', StudentGrade::where('user_id', $passing->id)->where('subject_code', 'CC101')->first()->final_grade);
        $this->assertSame('Failed', StudentGrade::where('user_id', $failing->id)->where('subject_code', 'CC101')->first()->status);
    }

    public function test_registrar_approve_rolls_back_entirely_if_one_item_fails(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC102']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $goodStudent = User::factory()->create(['role' => 'student']);
        $badStudent = User::factory()->create(['role' => 'student']);
        $submission = GradeSubmission::create([
            'section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_registrar',
        ]);
        // First item is valid and would succeed on its own; second item has a status value
        // outside the student_grades DB enum, forcing StudentGrade::updateOrCreate() to throw
        // partway through the loop. A real request could never produce this (faculty entry is
        // constrained to Passed/Failed by the >=75 cutoff), but a test fixture can construct it
        // directly to prove the transaction rolls back the whole batch, not just the failing row.
        GradeSubmissionItem::create(['grade_submission_id' => $submission->id, 'user_id' => $goodStudent->id, 'final_grade' => '88', 'status' => 'Passed']);
        GradeSubmissionItem::create(['grade_submission_id' => $submission->id, 'user_id' => $badStudent->id, 'final_grade' => '60', 'status' => 'Bogus']);

        $response = $this->actingAs($registrar)->post("/registrar/grades/{$submission->id}/approve");

        $response->assertStatus(500);

        $this->assertDatabaseCount('student_grades', 0);
        $submission->refresh();
        $this->assertSame('pending_registrar', $submission->status);
    }

    public function test_registrar_can_reject_and_it_returns_to_draft(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $submission = GradeSubmission::create([
            'section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_registrar',
        ]);

        $response = $this->actingAs($registrar)->post("/registrar/grades/{$submission->id}/reject", [
            'remarks' => 'Missing a student.',
        ]);

        $response->assertRedirect();
        $submission->refresh();
        $this->assertSame('draft', $submission->status);
        $this->assertSame('registrar', $submission->rejected_by);
    }

    public function test_registrar_cannot_approve_a_submission_not_pending_registrar(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $submission = GradeSubmission::create([
            'section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair',
        ]);

        $response = $this->actingAs($registrar)->post("/registrar/grades/{$submission->id}/approve");

        $response->assertForbidden();
        $this->assertDatabaseCount('student_grades', 0);
    }

    public function test_non_registrar_cannot_approve(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $submission = GradeSubmission::create([
            'section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_registrar',
        ]);

        $response = $this->actingAs($chair)->post("/registrar/grades/{$submission->id}/approve");

        $response->assertForbidden();
    }
}
