<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetViaRegistrarTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_forgot_password_request_is_flagged_for_the_registrar_instead_of_emailed(): void
    {
        $student = User::factory()->create(['role' => 'student', 'login_id' => '2026-00200']);

        $this->post('/forgot-password', ['login_id' => '2026-00200'])->assertSessionHas('status');

        $this->assertNotNull($student->fresh()->password_reset_requested_at);
    }

    public function test_non_student_forgot_password_request_is_not_flagged_for_the_registrar(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'login_id' => '2026-00201']);

        $this->post('/forgot-password', ['login_id' => '2026-00201'])->assertSessionHas('status');

        $this->assertNull($cashier->fresh()->password_reset_requested_at);
    }

    public function test_unknown_login_id_gets_the_same_response_as_a_real_student(): void
    {
        User::factory()->create(['role' => 'student', 'login_id' => '2026-00202']);

        $knownMessage = $this->post('/forgot-password', ['login_id' => '2026-00202'])->getSession()->get('status');
        $unknownMessage = $this->post('/forgot-password', ['login_id' => 'no-such-login-id'])->getSession()->get('status');

        $this->assertSame($knownMessage, $unknownMessage);
    }

    public function test_registrar_dashboard_lists_pending_password_reset_requests(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Requests Pending Student',
            'password_reset_requested_at' => now(),
        ]);

        $response = $this->actingAs($registrar)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/registrar/dashboard');

        $response->assertOk();
        $response->assertSee($student->name);
    }

    public function test_logged_in_student_reset_goes_to_the_registrar_not_email(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->postJson('/profile/password/reset-link')->assertOk()
            ->assertJsonPath('viaRegistrar', true);

        $this->assertNotNull($student->fresh()->password_reset_requested_at);
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_students_cannot_reset_through_an_email_token(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@example.com', 'password' => Hash::make('old-password')]);
        $token = \Illuminate\Support\Facades\Password::createToken($student);

        $this->post('/reset-password', [
            'token' => $token, 'email' => 'stu@example.com',
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $student->fresh()->password));
    }

    public function test_registrar_can_decline_a_reset_request(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student', 'password' => Hash::make('old-password'), 'password_reset_requested_at' => now()]);

        $this->actingAs($registrar)->withSession(['auth.password_confirmed_at' => time()])
            ->post("/registrar/password-resets/{$student->id}/decline")
            ->assertRedirect()->assertSessionHas('success');

        $student->refresh();
        $this->assertNull($student->password_reset_requested_at);
        $this->assertTrue(Hash::check('old-password', $student->password));
        $this->assertFalse((bool) $student->must_change_password);
    }

    public function test_registrar_can_reset_a_students_flagged_password(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create([
            'role' => 'student',
            'password' => Hash::make('old-password'),
            'password_reset_requested_at' => now(),
        ]);

        $response = $this->actingAs($registrar)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post("/registrar/password-resets/{$student->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertNull($student->password_reset_requested_at);
        $this->assertTrue($student->must_change_password);
        $this->assertFalse(Hash::check('old-password', $student->password));
    }

    public function test_temporary_password_stays_on_the_dashboard_until_dismissed(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student', 'login_id' => 'STU-777', 'password_reset_requested_at' => now()]);
        $this->actingAs($registrar)->withSession(['auth.password_confirmed_at' => time()]);

        $this->post("/registrar/password-resets/{$student->id}")->assertRedirect();

        // Shown on the dashboard, and still there after a reload.
        $first = $this->get('/registrar/dashboard')->assertOk()->assertSee('tempPasswords', false)->assertSee('STU-777', false);
        $this->get('/registrar/dashboard')->assertSee('STU-777', false);

        preg_match('/&quot;password&quot;:&quot;([A-Za-z0-9]{12})&quot;/', $first->getContent(), $m);
        $this->assertNotEmpty($m, 'temporary password should be in the dashboard context');
        $this->assertTrue(Hash::check($m[1], $student->fresh()->password));

        $this->post("/registrar/password-resets/{$student->id}/dismiss")->assertRedirect();
        $this->get('/registrar/dashboard')->assertDontSee('STU-777', false);
    }

    public function test_temporary_passwords_avoid_look_alike_characters(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $this->actingAs($registrar)->withSession(['auth.password_confirmed_at' => time()]);

        // No I, l, 1, O, o, 0 — it's read out or copied by hand at the counter.
        for ($i = 0; $i < 25; $i++) {
            $student = User::factory()->create(['role' => 'student', 'password_reset_requested_at' => now()]);
            $this->post("/registrar/password-resets/{$student->id}");
            $password = collect(session('tempPasswords'))->firstWhere('studentId', $student->id)['password'];
            $this->assertMatchesRegularExpression('/^[A-HJ-NP-Za-km-np-z2-9]{12}$/', $password);
        }
    }

    public function test_temporary_password_expires_after_fifteen_minutes(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student', 'login_id' => 'STU-888', 'password_reset_requested_at' => now()]);
        $this->actingAs($registrar)->withSession(['auth.password_confirmed_at' => time()]);
        $this->post("/registrar/password-resets/{$student->id}");

        $this->travel(16)->minutes();
        $this->withSession(['auth.password_confirmed_at' => time()])
            ->get('/registrar/dashboard')->assertDontSee('STU-888', false);
    }

    public function test_password_reset_action_requires_step_up_reauth(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student', 'password_reset_requested_at' => now()]);

        $response = $this->actingAs($registrar)->post("/registrar/password-resets/{$student->id}");

        $response->assertRedirect('/confirm-password');
        $this->assertNotNull($student->fresh()->password_reset_requested_at);
    }

    public function test_only_registrar_or_admission_roles_can_reset_a_password(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student', 'password_reset_requested_at' => now()]);

        $response = $this->actingAs($cashier)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post("/registrar/password-resets/{$student->id}");

        $response->assertForbidden();
    }

    public function test_user_flagged_for_forced_password_change_is_redirected_away_from_other_pages(): void
    {
        $student = User::factory()->create(['role' => 'student', 'must_change_password' => true]);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertRedirect('/force-password-change');
    }

    public function test_force_password_change_page_itself_is_reachable_without_a_redirect_loop(): void
    {
        $student = User::factory()->create(['role' => 'student', 'must_change_password' => true]);

        $this->actingAs($student)->get('/force-password-change')->assertOk();
    }

    public function test_a_user_not_flagged_cannot_change_their_password_via_the_forced_change_form(): void
    {
        $student = User::factory()->create(['role' => 'student', 'password' => Hash::make('old-password'), 'must_change_password' => false]);

        $this->actingAs($student)->get('/force-password-change')->assertForbidden();

        $response = $this->actingAs($student)->post('/force-password-change', [
            'password' => 'attacker-set-password',
            'password_confirmation' => 'attacker-set-password',
        ]);

        $response->assertForbidden();
        $this->assertTrue(Hash::check('old-password', $student->fresh()->password));
    }

    public function test_submitting_a_new_password_clears_the_forced_change_flag_and_logs_in_normally(): void
    {
        $student = User::factory()->create(['role' => 'student', 'must_change_password' => true]);

        $response = $this->actingAs($student)->post('/force-password-change', [
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $student->refresh();
        $this->assertFalse($student->must_change_password);
        $this->assertTrue(Hash::check('brand-new-password', $student->password));
    }
}
