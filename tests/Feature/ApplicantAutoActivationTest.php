<?php

namespace Tests\Feature;

use App\Mail\ApplicantAccountCreated;
use App\Models\TransactionLedger;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Covers the two places an applicant's reservation fee gets confirmed paid,
 * both of which must auto-activate the student account (no Registrar
 * verify/decline step): the PayMongo settlement path, and the Registrar's
 * manual "mark as paid" counter-payment toggle.
 */
class ApplicantAutoActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_paymongo_settlement_of_a_reservation_fee_activates_the_account(): void
    {
        Mail::fake();

        $applicant = User::factory()->create(['role' => 'applicant', 'is_reserved' => false]);
        TransactionLedger::create([
            'user_id' => $applicant->id,
            'gateway' => 'paymongo',
            'checkout_session_id' => 'cs_test_123',
            'amount' => 500,
            'fee_type' => 'reservation',
            'status' => 'Pending',
            'reference_no' => 'RES-TEST123',
        ]);

        $result = app(PaymentService::class)->settleByCheckoutSessionId('cs_test_123');

        $this->assertTrue($result['ok']);
        $applicant->refresh();
        $this->assertTrue($applicant->is_reserved);
        $this->assertSame('student', $applicant->role);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Student Account Auto-Created']);
        Mail::assertSent(ApplicantAccountCreated::class);
    }

    public function test_a_non_reservation_paymongo_settlement_does_not_touch_role(): void
    {
        Mail::fake();

        $applicant = User::factory()->create(['role' => 'applicant']);
        TransactionLedger::create([
            'user_id' => $applicant->id,
            'gateway' => 'paymongo',
            'checkout_session_id' => 'cs_test_456',
            'amount' => 1000,
            'fee_type' => 'tuition',
            'status' => 'Pending',
            'reference_no' => 'TXN-TEST456',
        ]);

        app(PaymentService::class)->settleByCheckoutSessionId('cs_test_456');

        $this->assertSame('applicant', $applicant->fresh()->role);
        Mail::assertNothingSent();
    }

    public function test_registrar_marking_reservation_paid_at_the_counter_activates_the_account(): void
    {
        Mail::fake();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create(['role' => 'applicant', 'is_reserved' => false]);

        $response = $this->actingAs($registrar)->post("/registrar/toggle-reservation/{$applicant->id}");

        $response->assertRedirect();
        $applicant->refresh();
        $this->assertTrue($applicant->is_reserved);
        $this->assertSame('student', $applicant->role);
        Mail::assertSent(ApplicantAccountCreated::class);
    }

    public function test_unmarking_reservation_does_not_activate_an_account(): void
    {
        Mail::fake();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create(['role' => 'applicant', 'is_reserved' => true]);

        $this->actingAs($registrar)->post("/registrar/toggle-reservation/{$applicant->id}");

        $applicant->refresh();
        $this->assertFalse($applicant->is_reserved);
        $this->assertSame('applicant', $applicant->role);
        Mail::assertNothingSent();
    }

    public function test_slot_count_still_reflects_reality_after_auto_activation(): void
    {
        Mail::fake();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create([
            'role' => 'applicant', 'is_reserved' => false, 'program_key' => 'bsit',
        ]);
        $limit = \App\Models\AdmissionSlotLimit::forProgram('bsit', 'BS Information Technology', '2026-2027');
        $limit->update(['total_slots' => 10, 'sections' => 1]);

        $before = $limit->slotsLeft();
        $this->actingAs($registrar)->post("/registrar/toggle-reservation/{$applicant->id}");

        $this->assertSame($before - 1, $limit->fresh()->slotsLeft());
        $this->assertSame('student', $applicant->fresh()->role);
    }

    public function test_registrar_verify_and_decline_applicant_routes_no_longer_exist(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create(['role' => 'applicant']);

        $this->actingAs($registrar)->post("/registrar/verify-applicant/{$applicant->id}")->assertNotFound();
        $this->actingAs($registrar)->post("/registrar/decline-applicant/{$applicant->id}")->assertNotFound();
    }

    public function test_registrar_can_activate_an_applicant_who_never_reserved_a_slot(): void
    {
        Mail::fake();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create([
            'role' => 'applicant', 'is_reserved' => false, 'wants_reservation' => false,
        ]);

        $response = $this->actingAs($registrar)->post("/registrar/activate-applicant/{$applicant->id}");

        $response->assertRedirect();
        $applicant->refresh();
        $this->assertSame('student', $applicant->role);
        $this->assertFalse($applicant->is_reserved);
        Mail::assertSent(ApplicantAccountCreated::class);
    }

    public function test_activate_applicant_route_404s_for_a_user_who_is_not_an_applicant(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($registrar)->post("/registrar/activate-applicant/{$student->id}")->assertNotFound();
    }
}
