<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSelfEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_their_profile_edit_form(): void
    {
        $user = User::factory()->create(['contact_number' => '09171234567', 'address' => 'Cabuyao, Laguna']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('09171234567');
        $response->assertSee('Cabuyao, Laguna');
    }

    public function test_user_can_update_contact_number_and_address(): void
    {
        $user = User::factory()->create(['contact_number' => '09170000000', 'address' => 'Old Address']);

        $response = $this->actingAs($user)->put('/profile', [
            'email' => $user->email,
            'contact_number' => '09189999999',
            'address' => 'New Address, Cabuyao',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('09189999999', $user->fresh()->contact_number);
        $this->assertSame('New Address, Cabuyao', $user->fresh()->address);
    }

    public function test_user_can_update_their_email_to_an_unused_address(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);

        $response = $this->actingAs($user)->put('/profile', [
            'email' => 'new@example.com',
            'contact_number' => $user->contact_number,
            'address' => $user->address,
        ]);

        $response->assertRedirect();
        $this->assertSame('new@example.com', $user->fresh()->email);
    }

    public function test_user_cannot_update_email_to_one_already_taken(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $response = $this->actingAs($user)->put('/profile', [
            'email' => 'taken@example.com',
            'contact_number' => $user->contact_number,
            'address' => $user->address,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame('mine@example.com', $user->fresh()->email);
    }

    public function test_a_user_can_keep_their_own_email_unchanged(): void
    {
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $response = $this->actingAs($user)->put('/profile', [
            'email' => 'mine@example.com',
            'contact_number' => '09171112222',
            'address' => 'Some Address',
        ]);

        $response->assertSessionDoesntHaveErrors('email');
        $this->assertSame('09171112222', $user->fresh()->contact_number);
    }

    public function test_role_and_login_id_cannot_be_changed_through_the_profile_form(): void
    {
        $user = User::factory()->create(['role' => 'student', 'login_id' => '2026-00001']);

        $this->actingAs($user)->put('/profile', [
            'email' => $user->email,
            'contact_number' => '09170001111',
            'address' => 'Somewhere',
            'role' => 'admin',
            'login_id' => 'hacked-id',
        ]);

        $fresh = $user->fresh();
        $this->assertSame('student', $fresh->role);
        $this->assertSame('2026-00001', $fresh->login_id);
    }
}
