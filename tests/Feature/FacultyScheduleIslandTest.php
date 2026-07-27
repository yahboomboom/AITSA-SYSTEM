<?php

namespace Tests\Feature;

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
}
