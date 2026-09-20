<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiFoundationSidebarRestyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_renders_the_navy_sidebar(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="app-sidebar"', false);
        $response->assertSee('bg-brandNavy', false);
        $response->assertSee('border-brandGold bg-white/5', false);
    }

    public function test_admin_dashboard_renders_the_navy_sidebar_with_working_nav_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('bg-brandNavy', false);
        $response->assertSee('border-brandGold bg-white/5', false);
        $response->assertSee(route('admin.departments'), false);
    }

    public function test_department_officer_dashboard_still_scopes_the_sidebar_label_to_their_department(): void
    {
        $department = Department::factory()->create(['name' => 'Library']);
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);

        $response = $this->actingAs($officer)->get('/department/dashboard');

        $response->assertOk();
        $response->assertSee('bg-brandNavy', false);
        $response->assertSee('border-brandGold bg-white/5', false);
        $response->assertSee('text-white/40 mb-3">Library', false);
    }
}
