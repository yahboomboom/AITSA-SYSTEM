<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDepartmentsIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_departments_page_renders_the_react_island_mount_point_with_real_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Department::factory()->create(['name' => 'Library', 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin/departments');

        $response->assertOk();
        $response->assertSee('id="admin-departments-root"', false);
        $response->assertSee('Library');
    }

    public function test_departments_page_handles_no_departments_yet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/departments');

        $response->assertOk();
        $response->assertSee('&quot;departments&quot;:[]', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/departments')->assertRedirect();
    }

    public function test_non_admin_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/departments')->assertForbidden();
    }
}
