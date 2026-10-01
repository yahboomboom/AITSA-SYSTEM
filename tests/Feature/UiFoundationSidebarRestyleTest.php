<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiFoundationSidebarRestyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_renders_the_light_sidebar_and_blue_header(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="app-sidebar"', false);
        $response->assertSee('ui-sidebar', false);
        $response->assertSee('ui-nav-active', false);
        $response->assertSee('ui-appbar', false);
    }

    public function test_admin_dashboard_renders_the_light_sidebar_with_working_nav_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('ui-sidebar', false);
        $response->assertSee('ui-nav-active', false);
        $response->assertSee('ui-appbar', false);
        $response->assertSee(route('admin.departments'), false);
    }

    public function test_every_staff_role_gets_the_light_sidebar_and_blue_header(): void
    {
        foreach (['registrar' => '/registrar/dashboard', 'cashier' => '/cashier/dashboard', 'chair' => '/approver/dashboard', 'faculty' => '/faculty/schedule'] as $role => $url) {
            $html = $this->actingAs(User::factory()->create(['role' => $role]))->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('ui-appbar', $html, "$url header");
            if ($role !== 'faculty') { // faculty pages are header-only, no sidebar
                $this->assertStringContainsString('ui-sidebar', $html, "$url sidebar");
            }
            $this->assertStringNotContainsString('bg-brandNavy transition-all', $html, "$url still has the old navy sidebar");
        }
    }

    public function test_department_officer_dashboard_still_scopes_the_sidebar_label_to_their_department(): void
    {
        $department = Department::factory()->create(['name' => 'Library']);
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);

        $response = $this->actingAs($officer)->get('/department/dashboard');

        $response->assertOk();
        $response->assertSee('ui-sidebar', false);
        $response->assertSee('ui-nav-active', false);
        $response->assertSee('ui-appbar', false);
        $response->assertSee('ui-sidebar-muted mb-3">Library', false);
    }
}
