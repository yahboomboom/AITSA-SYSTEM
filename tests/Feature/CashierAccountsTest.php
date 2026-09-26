<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_account_has_no_quick_approve_form_writing_fake_data(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'admission_status' => 'Approved', 'chair_status' => 'Pending',
            'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
        ]);

        $response = $this->actingAs($cashier)->withSession(['auth.password_confirmed_at' => time()])->get('/cashier/accounts');

        $response->assertOk();
        $response->assertDontSee('Quick Approve');
        $response->assertDontSee('₱ 3,500.00', false);
        $response->assertDontSee('name="amount"', false);
        // "Review in Cashier Hub" is rendered client-side by the React island now, so
        // the safe-path assertion here is that the real dashboard URL (not a form
        // posting fake amounts) reaches the page as data.
        $response->assertSee(route('cashier.dashboard'), false);
        $response->assertSee(route('cashier.transactions'), false);
    }

    public function test_accounts_page_does_not_list_a_students_clearance_from_a_past_term(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student']);

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
        $past = Clearance::initializeFor($student->id, '2026-2027', 1);

        Setting::put('semester', '2');
        Setting::clearCache();
        $current = Clearance::initializeFor($student->id, '2026-2027', 2);

        $response = $this->actingAs($cashier)->withSession(['auth.password_confirmed_at' => time()])->get('/cashier/accounts');

        $response->assertOk();
        $response->assertDontSee('#' . sprintf('%04d', $past->id));
        $response->assertSee('#' . sprintf('%04d', $current->id));
        $occurrences = substr_count($response->getContent(), e($student->name));
        $this->assertSame(1, $occurrences);
    }
}
