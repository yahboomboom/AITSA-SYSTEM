<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Setting;
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

    public function test_dashboard_does_not_list_a_students_clearance_from_a_past_term(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student']);

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 1);

        Setting::put('semester', '2');
        Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 2);

        $response = $this->actingAs($cashier)->get('/cashier/dashboard');

        $response->assertOk();
        $json = $response->getContent();
        $occurrences = substr_count($json, '&quot;studentName&quot;:&quot;' . $student->name . '&quot;');
        $this->assertSame(1, $occurrences);
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
