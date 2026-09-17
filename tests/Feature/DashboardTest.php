<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Department;
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

    public function test_dashboard_context_carries_the_real_clearance_completion_percent(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $department = Department::factory()->create(['is_active' => true]);
        $clearance = Clearance::create([
            'user_id' => $student->id,
            'school_year' => '2026-2027',
            'semester' => 1,
            'chair_status' => 'Approved',
            'cashier_status' => 'Pending',
            'registrar_status' => 'Pending',
        ]);
        $clearance->items()->create(['department_id' => $department->id, 'status' => 'Approved']);

        $response = $this->actingAs($student)->get('/dashboard');

        // chair + item approved = 2 of (3 legacy stages + 1 item) = 50%
        $response->assertSee('&quot;clearancePercent&quot;:50', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/dashboard')->assertRedirect();
    }
}
