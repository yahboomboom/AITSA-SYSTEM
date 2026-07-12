<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederFacultyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_links_rooms_and_faculty_without_double_booking(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', ['login_id' => 'faculty01', 'role' => 'faculty']);
        $this->assertDatabaseHas('users', ['login_id' => 'faculty02', 'role' => 'faculty']);
        $this->assertDatabaseHas('rooms', ['name' => 'Google Meet A', 'type' => 'virtual']);

        $this->assertSame(0, Section::whereNull('room_id')->count(), 'every seeded section should link a room');
        $this->assertTrue(Section::whereNotNull('faculty_id')->exists(), 'some sections should be assigned to faculty');

        foreach (User::where('role', 'faculty')->get() as $prof) {
            $sections = $prof->taughtSections()->get();
            foreach ($sections as $a) {
                foreach ($sections as $b) {
                    if ($a->id >= $b->id || $a->school_year !== $b->school_year) {
                        continue;
                    }
                    $this->assertFalse($a->overlaps($b), "Seeder double-booked {$prof->login_id} (sections {$a->id} and {$b->id}).");
                }
            }
        }
    }
}
