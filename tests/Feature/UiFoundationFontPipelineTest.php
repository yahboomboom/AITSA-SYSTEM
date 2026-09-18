<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiFoundationFontPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_loads_the_shared_font_partial(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('fonts.googleapis.com/css2?family=Public+Sans', false);
        $response->assertSee('DM+Sans', false);
    }

    public function test_admin_dashboard_loads_the_shared_font_partial(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('fonts.googleapis.com/css2?family=Public+Sans', false);
    }

    public function test_login_page_is_untouched_by_the_new_font_partial(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('fonts.googleapis.com/css2?family=Public+Sans', false);
    }
}
