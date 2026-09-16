<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_form_renders(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_requesting_a_reset_link_by_login_id_sends_a_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['login_id' => '2026-00001', 'email' => 'student@example.com']);

        $response = $this->post('/forgot-password', ['login_id' => '2026-00001']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_requesting_a_reset_link_by_email_sends_a_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['login_id' => '2026-00002', 'email' => 'byemail@example.com']);

        $this->post('/forgot-password', ['login_id' => 'byemail@example.com']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_requesting_a_reset_link_for_an_unknown_identifier_does_not_leak_account_existence(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', ['login_id' => 'no-such-account@example.com']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_reset_password_form_renders_with_token_and_email(): void
    {
        $this->get('/reset-password/some-token?email=student@example.com')->assertOk();
    }

    public function test_submitting_a_valid_token_resets_the_password_and_allows_login(): void
    {
        $user = User::factory()->create([
            'login_id' => '2026-00003',
            'email' => 'reset@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('login'));
        $user->refresh();
        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->assertFalse(Hash::check('old-password', $user->password));
    }

    public function test_submitting_an_invalid_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create([
            'email' => 'badtoken@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'badtoken@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors();
        $user->refresh();
        $this->assertTrue(Hash::check('old-password', $user->password));
    }
}
