<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierDashboardIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_dashboard_renders_the_react_island_mount_point(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->get('/cashier/dashboard');

        $response->assertOk();
        $response->assertSee('id="cashier-dashboard-root"', false);
        $response->assertDontSee('Clearance Evaluation Queue');
        $response->assertDontSee('TXN-', false);
        $response->assertDontSee('₱ 3,500.00', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/cashier/dashboard')->assertRedirect();
    }

    public function test_student_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/cashier/dashboard')->assertForbidden();
    }
}
