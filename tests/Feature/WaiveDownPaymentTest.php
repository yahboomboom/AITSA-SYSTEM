<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaiveDownPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeClearance(): array
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Pending', 'registrar_status' => 'Approved',
        ]);

        return [$student, $clearance];
    }

    public function test_cashier_can_waive_the_down_payment_with_a_reason(): void
    {
        [$student, $clearance] = $this->makeClearance();
        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->post('/cashier/waive-down-payment', [
            'user_id' => $student->id,
            'reason' => 'Guidance office endorsed hardship case, family lost income this term.',
        ]);

        $response->assertRedirect(route('cashier.dashboard'));

        $clearance->refresh();
        $this->assertTrue($clearance->down_payment_waived);
        $this->assertSame('Guidance office endorsed hardship case, family lost income this term.', $clearance->down_payment_waived_reason);
        $this->assertSame($cashier->id, $clearance->down_payment_waived_by);
        $this->assertNotNull($clearance->down_payment_waived_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Down Payment Waived']);
    }

    public function test_reason_is_required(): void
    {
        [$student, $clearance] = $this->makeClearance();
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)->from('/cashier/dashboard')
            ->post('/cashier/waive-down-payment', ['user_id' => $student->id])
            ->assertSessionHasErrors('reason');

        $this->assertFalse($clearance->fresh()->down_payment_waived);
    }

    public function test_registrar_role_cannot_waive_a_down_payment(): void
    {
        [$student, $clearance] = $this->makeClearance();
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->post('/cashier/waive-down-payment', [
            'user_id' => $student->id, 'reason' => 'Test',
        ])->assertForbidden();
    }

    public function test_student_cannot_waive_a_down_payment(): void
    {
        [$student, $clearance] = $this->makeClearance();

        $this->actingAs($student)->post('/cashier/waive-down-payment', [
            'user_id' => $student->id, 'reason' => 'Test',
        ])->assertForbidden();
    }

    public function test_404_for_a_student_with_no_current_term_clearance(): void
    {
        $otherStudent = User::factory()->create(['role' => 'student']);
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)->post('/cashier/waive-down-payment', [
            'user_id' => $otherStudent->id, 'reason' => 'Test',
        ])->assertNotFound();
    }
}
