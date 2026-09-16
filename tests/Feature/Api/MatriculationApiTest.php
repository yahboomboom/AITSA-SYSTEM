<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculationApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Enrollment, 2: Section, 3: Section} */
    private function enrolledStudent(): array
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

        return [$user, $enrollment, $secA, $secB];
    }

    public function test_context_for_enrolled_student(): void
    {
        [$user, , $secA] = $this->enrolledStudent();

        $response = $this->actingAs($user)->getJson('/api/matriculation/context');

        $response->assertOk()
            ->assertJsonPath('window_open', true)
            ->assertJsonPath('enrollment.status', 'enrolled')
            ->assertJsonPath('request', null)
            ->assertJsonCount(2, 'enrollment.sections');
        $this->assertContains($secA->id, array_column($response->json('enrollment.sections'), 'id'));
        $this->assertIsArray($response->json('catalogue'));
    }

    public function test_context_without_enrollment(): void
    {
        Setting::put('change_matriculation_open', '0');
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA']);

        $response = $this->actingAs($user)->getJson('/api/matriculation/context');

        $response->assertOk()
            ->assertJsonPath('window_open', false)
            ->assertJsonPath('enrollment', null)
            ->assertJsonPath('catalogue', null);
    }

    public function test_submit_drop_via_api(): void
    {
        [$user, , $secA] = $this->enrolledStudent();

        $response = $this->actingAs($user)->postJson('/api/matriculation', [
            'items' => [['action' => 'drop', 'section_id' => $secA->id]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('request.status', 'pending')
            ->assertJsonPath('request.items.0.action', 'drop')
            ->assertJsonPath('request.items.0.section.code', 'SUBJ-A');
    }

    public function test_rule_failure_passes_through_as_409(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        Setting::put('change_matriculation_open', '0');

        $this->actingAs($user)->postJson('/api/matriculation', [
            'items' => [['action' => 'drop', 'section_id' => $secA->id]],
        ])->assertStatus(409)->assertJsonStructure(['message']);
    }

    public function test_malformed_payload_is_422(): void
    {
        [$user] = $this->enrolledStudent();

        $this->actingAs($user)->postJson('/api/matriculation', [
            'items' => [['action' => 'explode', 'section_id' => 1]],
        ])->assertStatus(422);
    }

    public function test_requires_student_role(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($chair)->getJson('/api/matriculation/context')->assertForbidden();
    }
}
