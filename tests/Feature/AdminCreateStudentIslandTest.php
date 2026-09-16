<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCreateStudentIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_student_page_renders_the_react_island_mount_point_with_real_programs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Program::factory()->create(['code' => 'BSIT', 'name' => 'BS Information Technology', 'level' => 'bachelor']);

        $response = $this->actingAs($admin)->get('/admin/students/create');

        $response->assertOk();
        $response->assertSee('id="admin-create-student-root"', false);
        $response->assertSee('BSIT');
        $response->assertSee('&quot;applicant&quot;:null', false);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/students/create')->assertRedirect();
    }

    public function test_non_admin_roles_are_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/students/create')->assertForbidden();
    }
}
