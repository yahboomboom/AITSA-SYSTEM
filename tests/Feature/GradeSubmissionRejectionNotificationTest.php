<?php

namespace Tests\Feature;

use App\Models\GradeSubmission;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\GradeSubmissionRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GradeSubmissionRejectionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_is_notified_when_chair_rejects(): void
    {
        Notification::fake();

        $chair = User::factory()->create(['role' => 'chair']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $submission = GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_chair']);

        $this->actingAs($chair)->post("/approver/grades/{$submission->id}/reject", ['remarks' => 'Fix row 2.']);

        Notification::assertSentTo($faculty, GradeSubmissionRejectedNotification::class, function ($notification) {
            return $notification->office === 'Department Chair' && $notification->remarks === 'Fix row 2.';
        });
    }

    public function test_faculty_is_notified_when_registrar_rejects(): void
    {
        Notification::fake();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $submission = GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id, 'status' => 'pending_registrar']);

        $this->actingAs($registrar)->post("/registrar/grades/{$submission->id}/reject", ['remarks' => 'Missing a student.']);

        Notification::assertSentTo($faculty, GradeSubmissionRejectedNotification::class, function ($notification) {
            return $notification->office === 'Registrar' && $notification->remarks === 'Missing a student.';
        });
    }
}
