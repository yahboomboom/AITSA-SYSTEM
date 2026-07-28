<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarDashboardIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_dashboard_renders_the_react_island_mount_point(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $response = $this->actingAs($registrar)->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee('id="registrar-dashboard-root"', false);
        $response->assertDontSee('Student Document Submissions');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/registrar/dashboard')->assertRedirect();
    }

    public function test_student_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/registrar/dashboard')->assertForbidden();
    }
}
