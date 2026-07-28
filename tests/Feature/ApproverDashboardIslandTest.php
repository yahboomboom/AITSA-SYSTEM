<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproverDashboardIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_approver_dashboard_renders_the_react_island_mount_point(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);

        $response = $this->actingAs($chair)->get('/approver/dashboard');

        $response->assertOk();
        $response->assertSee('id="approver-dashboard-root"', false);
        $response->assertDontSee('Change of Matriculation Requests');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/approver/dashboard')->assertRedirect();
    }

    public function test_student_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/approver/dashboard')->assertForbidden();
    }
}
