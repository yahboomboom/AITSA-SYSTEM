<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceHoldTest extends TestCase
{
    use RefreshDatabase;

    private function makeClearance(): Clearance
    {
        $student = User::factory()->create(['role' => 'student']);

        return Clearance::create([
            'user_id' => $student->id,
            'admission_status' => 'Approved', 'chair_status' => 'Pending',
            'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
        ]);
    }

    public function test_chair_hold_requires_remarks_and_sets_hold_status(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $clearance = $this->makeClearance();

        $this->actingAs($chair)->from('/approver/dashboard')
            ->post("/approver/hold/{$clearance->id}", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($chair)
            ->post("/approver/hold/{$clearance->id}", ['remarks' => 'Missing lab clearance.'])
            ->assertRedirect(route('approver.dashboard'));

        $clearance->refresh();
        $this->assertSame('Hold', $clearance->chair_status);
        $this->assertSame('Missing lab clearance.', $clearance->remarks);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Clearance Held']);
    }

    public function test_chair_approve_clears_remarks(): void
    {
        $chair = User::factory()->create(['role' => 'chair']);
        $clearance = $this->makeClearance();
        $clearance->update(['chair_status' => 'Hold', 'remarks' => 'Old remark']);

        $this->actingAs($chair)->post("/approver/sign/{$clearance->id}");

        $clearance->refresh();
        $this->assertSame('Approved', $clearance->chair_status);
        $this->assertNull($clearance->remarks);
    }

    public function test_registrar_hold_requires_remarks(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $clearance = $this->makeClearance();

        $this->actingAs($registrar)->from('/registrar/dashboard')
            ->post("/registrar/hold/{$clearance->id}", [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($registrar)
            ->post("/registrar/hold/{$clearance->id}", ['remarks' => 'Missing Form 137.'])
            ->assertRedirect(route('registrar.dashboard'));

        $clearance->refresh();
        $this->assertSame('Hold', $clearance->registrar_status);
        $this->assertSame('Missing Form 137.', $clearance->remarks);
    }

    public function test_cashier_hold_requires_remarks(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $clearance = $this->makeClearance();

        $this->actingAs($cashier)->from('/cashier/dashboard')
            ->post('/cashier/hold', ['user_id' => $clearance->user_id])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($cashier)
            ->post('/cashier/hold', ['user_id' => $clearance->user_id, 'remarks' => 'Balance dispute.'])
            ->assertRedirect();

        $clearance->refresh();
        $this->assertSame('Hold', $clearance->cashier_status);
        $this->assertSame('Balance dispute.', $clearance->remarks);
    }

    public function test_cashier_approve_clears_remarks(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $clearance = $this->makeClearance();
        $clearance->update(['cashier_status' => 'Hold', 'remarks' => 'Old remark']);

        $this->actingAs($cashier)->post('/cashier/approve', [
            'user_id' => $clearance->user_id, 'reference_no' => 'TXN-1', 'amount' => '1000',
        ]);

        $clearance->refresh();
        $this->assertSame('Approved', $clearance->cashier_status);
        $this->assertNull($clearance->remarks);
    }

    public function test_remarks_shown_on_student_clearance_page(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Hold', 'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
            'remarks' => 'Please see the Department Chair.',
        ]);

        $this->actingAs($student)->get('/clearance')
            ->assertOk()
            ->assertSee('"remarks":"Please see the Department Chair."');
    }
}
