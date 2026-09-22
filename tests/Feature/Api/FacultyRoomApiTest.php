<?php

namespace Tests\Feature\Api;

use App\Models\Room;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyRoomApiTest extends TestCase
{
    use RefreshDatabase;

    private User $chair;

    protected function setUp(): void
    {
        parent::setUp();
        // Faculty/room management moved from Registrar to the Dept Chair
        // alongside section scheduling — see
        // docs/superpowers/specs/2026-09-12-scheduling-to-chair-design.md.
        $this->chair = User::factory()->create(['role' => 'chair']);
    }

    public function test_chair_can_create_and_list_rooms(): void
    {
        $this->actingAs($this->chair)
            ->postJson('/api/admin/rooms', ['name' => 'Rm 101', 'type' => 'physical'])
            ->assertStatus(201)
            ->assertJsonPath('room.name', 'Rm 101');

        $this->actingAs($this->chair)->getJson('/api/admin/rooms')
            ->assertOk()
            ->assertJsonPath('rooms.0.name', 'Rm 101');

        $this->assertDatabaseHas('audit_logs', ['action' => 'Room Created']);
    }

    public function test_duplicate_room_name_is_rejected(): void
    {
        Room::create(['name' => 'Rm 101', 'type' => 'physical']);

        $this->actingAs($this->chair)
            ->postJson('/api/admin/rooms', ['name' => 'Rm 101', 'type' => 'physical'])
            ->assertStatus(422);
    }

    public function test_invalid_room_type_is_rejected(): void
    {
        $this->actingAs($this->chair)
            ->postJson('/api/admin/rooms', ['name' => 'Rm 102', 'type' => 'hologram'])
            ->assertStatus(422);
    }

    public function test_chair_can_create_and_list_faculty(): void
    {
        $this->actingAs($this->chair)
            ->postJson('/api/admin/faculty', ['name' => 'Prof. Liza Ramos', 'login_id' => 'faculty09'])
            ->assertStatus(201)
            ->assertJsonPath('faculty.name', 'Prof. Liza Ramos')
            ->assertJsonPath('faculty.sections_count', 0);

        $this->assertDatabaseHas('users', ['login_id' => 'faculty09', 'role' => 'faculty']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Faculty Created']);

        $response = $this->actingAs($this->chair)->getJson('/api/admin/faculty');
        $response->assertOk();
        $this->assertContains('faculty09', array_column($response->json('faculty'), 'login_id'));
    }

    public function test_duplicate_faculty_login_id_is_rejected(): void
    {
        User::factory()->create(['login_id' => 'faculty09']);

        $this->actingAs($this->chair)
            ->postJson('/api/admin/faculty', ['name' => 'Prof. Dup', 'login_id' => 'faculty09'])
            ->assertStatus(422);
    }

    public function test_schedule_endpoint_returns_current_year_sections_only(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);
        $current = Section::factory()->create(['faculty_id' => $prof->id, 'school_year' => '2026-2027']);
        Section::factory()->create(['faculty_id' => $prof->id, 'school_year' => '2025-2026', 'days' => ['T']]);

        $response = $this->actingAs($this->chair)->getJson("/api/admin/faculty/{$prof->id}/schedule");

        $response->assertOk();
        $this->assertCount(1, $response->json('schedule'));
        $this->assertSame($current->id, $response->json('schedule.0.id'));
    }

    public function test_schedule_endpoint_rejects_non_faculty_target(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($this->chair)
            ->getJson("/api/admin/faculty/{$student->id}/schedule")
            ->assertStatus(422);
    }

    public function test_students_cannot_use_faculty_or_room_endpoints(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->getJson('/api/admin/faculty')->assertForbidden();
        $this->actingAs($student)->postJson('/api/admin/rooms', ['name' => 'X', 'type' => 'physical'])->assertForbidden();
    }

    public function test_chair_can_delete_an_unused_room(): void
    {
        $room = Room::create(['name' => 'Rm 999', 'type' => 'physical']);

        $this->actingAs($this->chair)->deleteJson("/api/admin/rooms/{$room->id}")
            ->assertOk();

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Room Deleted']);
    }

    public function test_room_assigned_to_a_section_cannot_be_deleted(): void
    {
        $room = Room::create(['name' => 'Rm 998', 'type' => 'physical']);
        Section::factory()->create(['room_id' => $room->id]);

        $this->actingAs($this->chair)->deleteJson("/api/admin/rooms/{$room->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_chair_can_delete_an_unused_faculty_member(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty', 'login_id' => 'faculty-del-01']);

        $this->actingAs($this->chair)->deleteJson("/api/admin/faculty/{$faculty->id}")
            ->assertOk();

        $this->assertNotNull($faculty->fresh()->deleted_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Faculty Deleted']);
    }

    public function test_faculty_teaching_a_section_cannot_be_deleted(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        Section::factory()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($this->chair)->deleteJson("/api/admin/faculty/{$faculty->id}")
            ->assertStatus(409);

        $this->assertNull($faculty->fresh()->deleted_at);
    }

    public function test_deleting_a_non_faculty_user_via_faculty_endpoint_is_rejected(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($this->chair)->deleteJson("/api/admin/faculty/{$student->id}")
            ->assertStatus(422);
    }

    public function test_students_cannot_delete_faculty_or_rooms(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $room = Room::create(['name' => 'Rm 997', 'type' => 'physical']);
        $faculty = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($student)->deleteJson("/api/admin/rooms/{$room->id}")->assertForbidden();
        $this->actingAs($student)->deleteJson("/api/admin/faculty/{$faculty->id}")->assertForbidden();
    }

    public function test_registrar_can_no_longer_use_faculty_or_room_endpoints(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->getJson('/api/admin/faculty')->assertForbidden();
        $this->actingAs($registrar)->postJson('/api/admin/rooms', ['name' => 'X', 'type' => 'physical'])->assertForbidden();
    }
}
