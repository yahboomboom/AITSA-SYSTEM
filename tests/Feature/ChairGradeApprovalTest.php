<?php

namespace Tests\Feature;

use App\Models\GradeSubmission;
use App\Models\GradeSubmissionItem;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChairGradeApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_chair_can_approve_a_pending_submission(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $submission = GradeSubmission::create([
            'section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair',
        ]);

        $response = $this->actingAs($chair)->post("/approver/grades/{$submission->id}/approve");

        $response->assertRedirect();
        $submission->refresh();
        $this->assertSame('pending_registrar', $submission->status);
        $this->assertSame($chair->id, $submission->chair_id);
        $this->assertNotNull($submission->chair_at);
    }

    public function test_chair_can_reject_a_pending_submission_with_remarks(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $submission = GradeSubmission::create([
            'section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair',
        ]);

        $response = $this->actingAs($chair)->post("/approver/grades/{$submission->id}/reject", [
            'remarks' => 'Please recheck the passing grades.',
        ]);

        $response->assertRedirect();
        $submission->refresh();
        $this->assertSame('draft', $submission->status);
        $this->assertSame('chair', $submission->rejected_by);
        $this->assertSame('Please recheck the passing grades.', $submission->remarks);
    }

    public function test_chair_reject_requires_remarks(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $submission = GradeSubmission::create([
            'section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair',
        ]);

        $response = $this->actingAs($chair)->post("/approver/grades/{$submission->id}/reject", []);

        $response->assertSessionHasErrors('remarks');
    }

    public function test_chair_cannot_approve_a_submission_not_pending_chair(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $submission = GradeSubmission::create([
            'section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'draft',
        ]);

        $response = $this->actingAs($chair)->post("/approver/grades/{$submission->id}/approve");

        $response->assertForbidden();
    }

    public function test_non_chair_cannot_approve(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $submission = GradeSubmission::create([
            'section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair',
        ]);

        $response = $this->actingAs($faculty)->post("/approver/grades/{$submission->id}/approve");

        $response->assertForbidden();
    }

    public function test_chair_approval_does_not_touch_student_grades(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        $student = User::factory()->create(['role' => 'student']);
        $submission = GradeSubmission::create([
            'section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair',
        ]);
        GradeSubmissionItem::create([
            'grade_submission_id' => $submission->id, 'user_id' => $student->id,
            'final_grade' => '88', 'status' => 'Passed',
        ]);

        $this->actingAs($chair)->post("/approver/grades/{$submission->id}/approve");

        $this->assertDatabaseMissing('student_grades', ['user_id' => $student->id]);
    }
}
