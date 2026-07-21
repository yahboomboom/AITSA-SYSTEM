<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_renders_the_react_island_mount_point(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="dashboard-root"', false);
        $response->assertDontSee('Welcome to');
        $response->assertDontSee('No announcements at this time.');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/dashboard')->assertRedirect();
    }
}
