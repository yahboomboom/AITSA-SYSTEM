<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\MatriculationChange;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\MatriculationChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChairMatriculationTest extends TestCase
{
    use RefreshDatabase;

    private function pendingChange(): MatriculationChange
    {
        Setting::put('change_matriculation_open', '1');

        $program = Program::factory()->create(['code' => 'BSOA', 'is_enrollable' => true]);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);

        $subjectA = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-A']);
        $subjectB = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-B']);
        $secA = Section::factory()->create(['subject_id' => $subjectA->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);
        $secB = Section::factory()->create(['subject_id' => $subjectB->id, 'days' => ['T', 'Th'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach([$secA->id, $secB->id]);

        return app(MatriculationChangeService::class)
            ->submit($user, [['action' => 'drop', 'section_id' => $secB->id]]);
    }

    public function test_dashboard_lists_pending_changes(): void
    {
        $change = $this->pendingChange();
        $chair = User::factory()->create(['role' => 'chair']);

        $response = $this->actingAs($chair)->get('/approver/dashboard');

        $response->assertOk();
        $this->assertTrue($response->viewData('pendingChanges')->contains('id', $change->id));
        $response->assertSee('id="approver-dashboard-root"', false);
        $response->assertSee('&quot;action&quot;:&quot;drop&quot;', false);
    }

    public function test_chair_can_approve(): void
    {
        $change = $this->pendingChange();
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($chair)
            ->post("/approver/matriculation/{$change->id}/approve")
            ->assertRedirect();

        $this->assertSame('approved', $change->fresh()->status);
        $this->assertCount(1, $change->enrollment->sections()->get());
    }

    public function test_chair_reject_requires_remarks(): void
    {
        $change = $this->pendingChange();
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($chair)
            ->from('/approver/dashboard')
            ->post("/approver/matriculation/{$change->id}/reject", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($chair)
            ->post("/approver/matriculation/{$change->id}/reject", ['remarks' => 'Keep your full load.'])
            ->assertRedirect();

        $this->assertSame('rejected', $change->fresh()->status);
    }

    public function test_students_cannot_hit_chair_routes(): void
    {
        $change = $this->pendingChange();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post("/approver/matriculation/{$change->id}/approve")
            ->assertForbidden();
    }
}
