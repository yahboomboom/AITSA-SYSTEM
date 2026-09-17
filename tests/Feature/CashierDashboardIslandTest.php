<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierDashboardIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_dashboard_renders_the_react_island_mount_point(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->get('/cashier/dashboard');

        $response->assertOk();
        $response->assertSee('id="cashier-dashboard-root"', false);
        $response->assertDontSee('Clearance Evaluation Queue');
        $response->assertDontSee('TXN-', false);
        $response->assertDontSee('₱ 3,500.00', false);
    }

    public function test_dashboard_does_not_list_a_students_clearance_from_a_past_term(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student']);

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 1);

        Setting::put('semester', '2');
        Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 2);

        $response = $this->actingAs($cashier)->get('/cashier/dashboard');

        $response->assertOk();
        $json = $response->getContent();
        $occurrences = substr_count($json, '&quot;studentName&quot;:&quot;' . $student->name . '&quot;');
        $this->assertSame(1, $occurrences);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/cashier/dashboard')->assertRedirect();
    }

    public function test_student_is_forbidden(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/cashier/dashboard')->assertForbidden();
    }

    public function test_dashboard_context_includes_down_payment_fields(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Pending', 'registrar_status' => 'Approved',
        ]);

        $response = $this->actingAs($cashier)->get('/cashier/dashboard');

        $response->assertOk();
        $response->assertSee('&quot;isDownPaymentMet&quot;:false', false);
        $response->assertSee('&quot;isDownPaymentWaived&quot;:false', false);
        $response->assertSee('data-waive-down-payment-url', false);
    }

    public function test_dashboard_context_reflects_a_met_down_payment(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Pending', 'registrar_status' => 'Approved',
        ]);
        // misc_fee defaults to 1500 with 0 units/tuition for a student with no program/section;
        // down_payment_percent defaults to 30 -> threshold 450.
        \App\Models\TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Settled', 'amount' => 450.00,
        ]);

        $response = $this->actingAs($cashier)->get('/cashier/dashboard');

        $response->assertSee('&quot;isDownPaymentMet&quot;:true', false);
    }

    public function test_dashboard_context_reflects_a_waived_down_payment(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Pending', 'registrar_status' => 'Approved',
            'down_payment_waived' => true,
        ]);

        $response = $this->actingAs($cashier)->get('/cashier/dashboard');

        $response->assertSee('&quot;isDownPaymentWaived&quot;:true', false);
    }

    public function test_dashboard_context_includes_held_status(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Hold', 'registrar_status' => 'Approved',
        ]);

        $response = $this->actingAs($cashier)->get('/cashier/dashboard');

        $response->assertSee('&quot;isHeld&quot;:true', false);
    }
}
