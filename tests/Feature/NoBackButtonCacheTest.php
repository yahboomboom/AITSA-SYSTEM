<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoBackButtonCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_are_not_cached_by_the_browser(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/dashboard');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertHeader('Pragma', 'no-cache');
    }

    public function test_the_login_page_is_not_cached_either(): void
    {
        $response = $this->get('/login');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_pressing_back_after_logout_cannot_reveal_a_cached_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/dashboard')->assertOk();

        $this->post('/logout');

        // The browser would normally re-request the page on Back only because
        // it isn't allowed to serve it from cache (asserted above); once it
        // does, the now-guest session must be bounced to login like any
        // other unauthenticated request.
        $this->get('/dashboard')->assertRedirect('/');
    }
}
