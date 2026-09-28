<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_cor_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'login_id' => 'S1001',
            'role' => 'student',
        ]);

        $this->actingAs($user)
            ->get('/cor')
            ->assertOk()
            ->assertSee('id="schedule-root"', false);
    }

    public function test_cor_page_chrome_is_labeled_schedule(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->get('/cor');

        $response->assertOk();
        $response->assertSee('<title>AITSA Portal | My Schedule</title>', false);
    }

    public function test_student_sidebar_links_to_schedule(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('href="' . route('cor') . '"', false);
        $response->assertSee('>Schedule<', false);
    }
}
