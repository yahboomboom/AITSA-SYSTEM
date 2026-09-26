<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StepUpReauthTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_page_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertOk();
        $response->assertSee('Confirm Your Password');
    }

    public function test_confirm_password_page_shows_the_cashiers_own_sidebar(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertOk();
        $response->assertSee('Transactions');
    }

    public function test_confirm_password_page_shows_the_registrars_own_sidebar(): void
    {
        $user = User::factory()->create(['role' => 'registrar']);

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertOk();
        $response->assertSee('Student Records');
    }

    public function test_confirm_password_page_shows_the_admission_roles_registrar_sidebar(): void
    {
        $user = User::factory()->create(['role' => 'admission']);

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertOk();
        $response->assertSee('Student Records');
    }

    public function test_confirm_password_page_shows_the_admins_own_sidebar(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertOk();
        $response->assertSee('Audit Trail');
    }

    public function test_confirm_password_page_never_leaks_real_notification_data(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertOk();
        $response->assertDontSee('notif-list', false);
    }

    public function test_guest_is_redirected_to_login_not_confirm_password(): void
    {
        $this->get('/confirm-password')->assertRedirect('/');
    }

    public function test_correct_password_confirms_and_redirects_to_the_intended_url(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($user)
            ->withSession(['url.intended' => 'http://localhost/cashier/transactions'])
            ->post('/confirm-password', ['password' => 'password']);

        $response->assertRedirect('http://localhost/cashier/transactions');
        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_correct_password_with_no_intended_url_falls_back_to_login_route(): void
    {
        // route('login') is '/' — AuthController::showLogin() already sends any
        // authenticated user on to their role's dashboard from there (a separate,
        // pre-existing redirect this controller doesn't need to re-prove); this
        // test only proves the fallback target itself is correct.
        $user = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($user)->post('/confirm-password', ['password' => 'password']);

        $response->assertRedirect('/');
    }

    public function test_wrong_password_does_not_confirm(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($user)->post('/confirm-password', ['password' => 'not-the-password']);

        $response->assertSessionHasErrors('password');
        $this->assertNull(session('auth.password_confirmed_at'));
    }

    public function test_wrong_password_records_an_audit_log_entry(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($user)->post('/confirm-password', ['password' => 'not-the-password']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Password Re-confirmation Failed',
            'target_type' => 'User',
            'target_id' => $user->id,
        ]);
    }

    public function test_every_sensitive_page_redirects_to_confirm_password_when_unconfirmed(): void
    {
        $pagesByRole = [
            '/cashier/transactions' => 'cashier',
            '/cashier/accounts' => 'cashier',
            '/cashier/billing' => 'cashier',
            '/registrar/reports' => 'registrar',
            '/registrar/students' => 'registrar',
            '/registrar/documents' => 'registrar',
            '/admin/students' => 'admin',
            '/admin/audit' => 'admin',
        ];

        foreach ($pagesByRole as $url => $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->get($url);

            $response->assertRedirect('/confirm-password');
        }
    }

    public function test_confirming_once_unlocks_every_sensitive_page_not_just_the_one_that_triggered_it(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($user)->post('/confirm-password', ['password' => 'password']);

        $this->actingAs($user)->get('/cashier/transactions')->assertOk();
        $this->actingAs($user)->get('/cashier/accounts')->assertOk();
        $this->actingAs($user)->get('/cashier/billing')->assertOk();
    }

    public function test_a_non_gated_page_is_never_redirected_to_confirm_password(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($user)->get('/cashier/dashboard')->assertOk();
    }

    public function test_guest_hitting_a_gated_page_goes_to_login_not_confirm_password(): void
    {
        $this->get('/cashier/transactions')->assertRedirect('/');
    }

    public function test_registrar_students_search_is_gated_like_its_parent_page(): void
    {
        $user = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($user)->get('/registrar/students/search?q=a')->assertRedirect('/confirm-password');
    }

    public function test_registrar_documents_search_is_gated_like_its_parent_page(): void
    {
        $user = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($user)->get('/registrar/documents/search?q=a')->assertRedirect('/confirm-password');
    }

    public function test_confirming_once_also_unlocks_the_registrar_search_endpoints(): void
    {
        $user = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($user)->post('/confirm-password', ['password' => 'password']);

        $this->actingAs($user)->get('/registrar/students/search?q=a')->assertOk();
        $this->actingAs($user)->get('/registrar/documents/search?q=a')->assertOk();
    }
}
