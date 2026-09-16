<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStudentsIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_page_renders_the_react_island_mount_point_with_real_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'student', 'name' => 'Island Test Student', 'login_id' => '2026-55555']);

        $response = $this->actingAs($admin)->get('/admin/students');

        $response->assertOk();
        $response->assertSee('id="admin-students-root"', false);
        $response->assertSee('Island Test Student');
        $response->assertSee('2026-55555');
    }

    public function test_students_page_handles_no_students_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/students');

        $response->assertOk();
        $response->assertSee('&quot;students&quot;:[]', false);
    }
}
