<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyScheduleIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_schedule_renders_the_react_island_mount_point(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);

        $response = $this->actingAs($prof)->get('/faculty/schedule');

        $response->assertOk();
        $response->assertSee('id="faculty-schedule-root"', false);
        $response->assertDontSee('Weekly Teaching Schedule');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/faculty/schedule')->assertRedirect();
    }

    public function test_student_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/faculty/schedule')->assertForbidden();
    }

    public function test_context_groups_multi_day_sections_and_flags_online_rooms(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'MULTI101']);
        Section::factory()->create([
            'subject_id' => $subject->id,
            'faculty_id' => $prof->id,
            'days' => ['M', 'W'],
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $context = $this->actingAs($prof)->get('/faculty/schedule')
            ->assertOk()->viewData('context');

        $labels = collect($context['days'])->pluck('label')->all();
        $this->assertSame(['Monday', 'Wednesday'], $labels);
        $this->assertStringContainsString('–', $context['days'][0]['sections'][0]['timeRange']);
        $this->assertStringNotContainsString('-', $context['days'][0]['sections'][0]['timeRange']);
    }

    public function test_context_flags_online_room_section_as_online(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'ONLINE101']);
        $room = Room::create(['name' => 'Zoom Room 1', 'type' => 'virtual']);
        Section::factory()->create([
            'subject_id' => $subject->id,
            'faculty_id' => $prof->id,
            'room_id' => $room->id,
            'days' => ['T'],
        ]);

        $context = $this->actingAs($prof)->get('/faculty/schedule')
            ->assertOk()->viewData('context');

        $this->assertTrue($context['days'][0]['sections'][0]['isOnline']);
    }

    public function test_context_flags_physical_room_section_as_not_online(): void
    {
        $prof = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'PHYS101']);
        $room = Room::create(['name' => 'Room 202', 'type' => 'physical']);
        Section::factory()->create([
            'subject_id' => $subject->id,
            'faculty_id' => $prof->id,
            'room_id' => $room->id,
            'days' => ['F'],
        ]);

        $context = $this->actingAs($prof)->get('/faculty/schedule')
            ->assertOk()->viewData('context');

        $this->assertFalse($context['days'][0]['sections'][0]['isOnline']);
    }
}
