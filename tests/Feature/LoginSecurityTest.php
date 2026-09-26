<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_wrong_password_and_unknown_account_return_the_same_error_message(): void
    {
        $user = User::factory()->create(['login_id' => '2026-00099', 'password' => Hash::make('real-password')]);

        $wrongPasswordMessage = $this->post('/login', ['login_id' => '2026-00099', 'password' => 'not-it'])
            ->getSession()->get('errors')->get('login_id')[0];

        $unknownAccountMessage = $this->post('/login', ['login_id' => 'no-such-account', 'password' => 'not-it'])
            ->getSession()->get('errors')->get('login_id')[0];

        $this->assertSame($wrongPasswordMessage, $unknownAccountMessage);
    }

    public function test_repeated_failed_login_attempts_are_throttled(): void
    {
        User::factory()->create(['login_id' => '2026-00100', 'password' => Hash::make('real-password')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['login_id' => '2026-00100', 'password' => 'not-it'])
                ->assertSessionHasErrors('login_id');
        }

        $this->post('/login', ['login_id' => '2026-00100', 'password' => 'not-it'])
            ->assertStatus(429);
    }

    public function test_repeated_forgot_password_requests_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['login_id' => 'someone@example.com'])
                ->assertSessionHas('status');
        }

        $this->post('/forgot-password', ['login_id' => 'someone@example.com'])
            ->assertStatus(429);
    }

    public function test_repeated_reset_password_submissions_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/reset-password', [
                'token' => 'not-a-real-token',
                'email' => 'someone@example.com',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])->assertSessionHasErrors();
        }

        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'someone@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertStatus(429);
    }
}
