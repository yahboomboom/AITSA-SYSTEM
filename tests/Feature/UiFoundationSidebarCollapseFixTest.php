<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiFoundationSidebarCollapseFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_sidebar_has_the_collapsed_state_classes(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="app-sidebar" class="group', false);
        $response->assertSee('lg:group-[.sidebar-collapsed]:hidden', false);
        $response->assertSee('lg:group-[.sidebar-collapsed]:justify-center', false);
    }

    public function test_admin_dashboard_sidebar_has_the_collapsed_state_classes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('id="app-sidebar" class="group', false);
        $response->assertSee('lg:group-[.sidebar-collapsed]:hidden', false);
    }
}
