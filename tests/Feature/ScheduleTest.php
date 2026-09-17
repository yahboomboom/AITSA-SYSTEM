<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_schedule_renders_the_react_island_mount_point(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/cor');

        $response->assertOk();
        $response->assertSee('id="schedule-root"', false);
        $response->assertDontSee('Certificate of Registration');
        $response->assertDontSee('Weekly Timetable');
        $response->assertDontSee('Schedule QR Code');
    }

    public function test_schedule_page_carries_the_real_current_term(): void
    {
        \App\Models\Setting::put('school_year', '2026-2027');
        \App\Models\Setting::put('semester', '2');
        \App\Models\Setting::clearCache();
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/cor');

        $response->assertOk();
        $response->assertSee('data-school-year="2026-2027"', false);
        $response->assertSee('data-semester="2"', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/cor')->assertRedirect();
    }
}
