<?php

namespace Tests\Feature\Api;

use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionConflictTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $prof;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->prof = User::factory()->create(['role' => 'faculty', 'name' => 'Prof. Cruz']);
        $this->room = Room::create(['name' => 'Rm 301', 'type' => 'physical']);
    }

    /** A valid POST /api/admin/sections payload; override what each test needs. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'subject_id' => Subject::factory()->create()->id,
            'block_label' => 'A',
            'days' => ['M', 'W'],
            'start_time' => '08:00',
            'end_time' => '09:30',
            'room' => 'Legacy Room',
            'professor' => 'Legacy Prof',
            'capacity' => 40,
            'school_year' => '2026-2027',
        ], $overrides);
    }

    public function test_same_faculty_overlap_is_rejected(): void
    {
        Section::factory()->create(['faculty_id' => $this->prof->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $this->prof->id, 'start_time' => '09:00', 'end_time' => '10:30']));

        $response->assertStatus(409);
        $this->assertStringContainsString('Prof. Cruz', $response->json('message'));
    }

    public function test_physical_room_overlap_is_rejected(): void
    {
        Section::factory()->create(['room_id' => $this->room->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['room_id' => $this->room->id]));

        $response->assertStatus(409);
        $this->assertStringContainsString('Rm 301', $response->json('message'));
    }

    public function test_virtual_room_overlap_is_allowed(): void
    {
        $meet = Room::create(['name' => 'Google Meet A', 'type' => 'virtual']);
        Section::factory()->create(['room_id' => $meet->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['room_id' => $meet->id]))
            ->assertStatus(201);
    }

    public function test_disjoint_days_do_not_conflict(): void
    {
        Section::factory()->create(['faculty_id' => $this->prof->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $this->prof->id, 'days' => ['T', 'Th']]))
            ->assertStatus(201);
    }

    public function test_disjoint_times_do_not_conflict(): void
    {
        Section::factory()->create(['faculty_id' => $this->prof->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $this->prof->id, 'start_time' => '09:30', 'end_time' => '11:00']))
            ->assertStatus(201);
    }

    public function test_different_school_year_does_not_conflict(): void
    {
        Section::factory()->create(['faculty_id' => $this->prof->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30', 'school_year' => '2025-2026']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $this->prof->id]))
            ->assertStatus(201);
    }

    public function test_update_does_not_conflict_with_itself(): void
    {
        $section = Section::factory()->create(['faculty_id' => $this->prof->id, 'room_id' => $this->room->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/sections/{$section->id}", ['capacity' => 45])
            ->assertOk()
            ->assertJsonPath('section.capacity', 45);
    }

    public function test_legacy_sections_without_fks_never_conflict(): void
    {
        Section::factory()->create(['days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30', 'room' => 'Same String', 'professor' => 'Same String']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['room' => 'Same String', 'professor' => 'Same String']))
            ->assertStatus(201);
    }

    public function test_faculty_id_must_reference_a_faculty_user(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $student->id]))
            ->assertStatus(422);
    }

    public function test_store_returns_faculty_name_and_room_label(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/sections', $this->payload(['faculty_id' => $this->prof->id, 'room_id' => $this->room->id]));

        $response->assertStatus(201)
            ->assertJsonPath('section.faculty_name', 'Prof. Cruz')
            ->assertJsonPath('section.room_label', 'Rm 301');
    }
}
