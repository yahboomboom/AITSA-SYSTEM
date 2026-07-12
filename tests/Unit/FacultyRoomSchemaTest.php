<?php

namespace Tests\Unit;

use App\Models\Room;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyRoomSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_and_faculty_links_on_sections(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty', 'name' => 'Prof. Reyes']);
        $room = Room::create(['name' => 'Rm 201', 'type' => 'physical']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id, 'room_id' => $room->id]);

        $this->assertTrue($section->faculty->is($faculty));
        $this->assertTrue($section->roomEntity->is($room));
        $this->assertTrue($room->isPhysical());
        $this->assertSame('Prof. Reyes', $section->facultyName());
        $this->assertSame('Rm 201', $section->roomLabel());
        $this->assertTrue($faculty->taughtSections()->whereKey($section->id)->exists());
        $this->assertTrue($room->sections()->whereKey($section->id)->exists());
    }

    public function test_labels_fall_back_to_legacy_strings(): void
    {
        $section = Section::factory()->create(['room' => 'Old Hall', 'professor' => 'TBA Faculty']);

        $this->assertNull($section->faculty_id);
        $this->assertNull($section->room_id);
        $this->assertSame('TBA Faculty', $section->facultyName());
        $this->assertSame('Old Hall', $section->roomLabel());
    }

    public function test_virtual_room_is_not_physical(): void
    {
        $room = Room::create(['name' => 'Google Meet A', 'type' => 'virtual']);

        $this->assertFalse($room->isPhysical());
    }
}
