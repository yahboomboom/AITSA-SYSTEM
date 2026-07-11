<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChairApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingEnrollment(): Enrollment
    {
        $enrollment = Enrollment::factory()->create(['type' => 'irregular', 'status' => 'pending']);
        $enrollment->sections()->attach(Section::factory()->create(['capacity' => 5])->id);

        return $enrollment;
    }

    public function test_chair_can_approve_pending_enrollment(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $enrollment = $this->makePendingEnrollment();

        $this->actingAs($chair)
            ->post("/approver/enrollments/{$enrollment->id}/approve")
            ->assertRedirect();

        $this->assertSame('enrolled', $enrollment->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Enrollment Approved', 'target_id' => $enrollment->id]);
    }

    public function test_chair_rejection_requires_remarks(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $enrollment = $this->makePendingEnrollment();

        $this->actingAs($chair)
            ->from('/approver/dashboard')
            ->post("/approver/enrollments/{$enrollment->id}/reject", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($chair)
            ->post("/approver/enrollments/{$enrollment->id}/reject", ['remarks' => 'Unit overload'])
            ->assertRedirect();

        $fresh = $enrollment->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Unit overload', $fresh->remarks);
    }

    public function test_approving_when_a_seat_vanished_fails_cleanly(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $enrollment = $this->makePendingEnrollment();
        $section = $enrollment->sections->first();
        $section->update(['capacity' => 1]);
        Enrollment::factory()->create(['status' => 'enrolled'])->sections()->attach($section->id);

        $this->actingAs($chair)
            ->post("/approver/enrollments/{$enrollment->id}/approve")
            ->assertSessionHas('error');

        $this->assertSame('pending', $enrollment->fresh()->status);
    }

    public function test_students_cannot_touch_the_queue(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $enrollment = $this->makePendingEnrollment();

        $this->actingAs($student)
            ->post("/approver/enrollments/{$enrollment->id}/approve")
            ->assertForbidden();
    }
}
