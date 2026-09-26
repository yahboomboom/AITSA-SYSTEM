<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfilePasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_their_password_with_the_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $response = $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $response = $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'not-the-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_new_password_must_be_at_least_8_characters(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $response = $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'old-password',
            'password' => 'short1',
            'password_confirmation' => 'short1',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_new_password_must_match_its_confirmation(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $response = $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'a-different-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_successful_change_records_an_audit_log_entry(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Password Changed',
            'target_type' => 'User',
            'target_id' => $user->id,
        ]);
    }

    public function test_guest_is_redirected(): void
    {
        $this->post('/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect();
    }

    public function test_repeated_wrong_current_password_attempts_are_throttled(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post('/profile/password', [
                'current_password' => 'not-the-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])->assertSessionHasErrors('current_password');
        }

        $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'not-the-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertStatus(429);
    }

    public function test_user_can_request_a_password_reset_link_emailed_to_themselves(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'me@example.com']);

        $response = $this->actingAs($user)->post('/profile/password/reset-link');

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_guest_cannot_request_a_password_reset_link_from_the_profile_route(): void
    {
        Notification::fake();

        $this->post('/profile/password/reset-link')->assertRedirect();

        Notification::assertNothingSent();
    }
}
