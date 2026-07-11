<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/ping')->assertUnauthorized();
    }

    public function test_session_user_is_accepted(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)
            ->getJson('/api/ping')
            ->assertOk()
            ->assertJson(['user' => $user->name]);
    }
}
