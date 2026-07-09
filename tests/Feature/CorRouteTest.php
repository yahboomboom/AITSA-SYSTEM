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
            ->assertSee('Certificate of Registration');
    }
}
