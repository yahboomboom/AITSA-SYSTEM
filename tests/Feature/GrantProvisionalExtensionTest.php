<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrantProvisionalExtensionTest extends TestCase
{
    use RefreshDatabase;

    private function makeClearance(): Clearance
    {
        $student = User::factory()->create(['role' => 'student']);

        return Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Pending',
        ]);
    }

    public function test_registrar_can_grant_a_provisional_extension_with_a_reason(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $clearance = $this->makeClearance();

        $response = $this->actingAs($registrar)->post("/registrar/clearances/{$clearance->id}/grant-provisional", [
            'reason' => 'Previous school confirmed Form 137 is in transit, expected next month.',
        ]);

        $response->assertRedirect(route('registrar.dashboard'));

        $clearance->refresh();
        $this->assertTrue($clearance->is_provisional);
        $this->assertSame('Previous school confirmed Form 137 is in transit, expected next month.', $clearance->provisional_reason);
        $this->assertSame($registrar->id, $clearance->provisional_granted_by);
        $this->assertNotNull($clearance->provisional_granted_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Provisional Extension Granted']);
    }

    public function test_reason_is_required(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $clearance = $this->makeClearance();

        $this->actingAs($registrar)->from('/registrar/dashboard')
            ->post("/registrar/clearances/{$clearance->id}/grant-provisional", [])
            ->assertSessionHasErrors('reason');

        $this->assertFalse($clearance->fresh()->is_provisional);
    }

    public function test_admission_role_cannot_grant_an_extension(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);
        $clearance = $this->makeClearance();

        $this->actingAs($admission)->post("/registrar/clearances/{$clearance->id}/grant-provisional", [
            'reason' => 'Test',
        ])->assertForbidden();
    }

    public function test_student_cannot_grant_an_extension(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = $this->makeClearance();

        $this->actingAs($student)->post("/registrar/clearances/{$clearance->id}/grant-provisional", [
            'reason' => 'Test',
        ])->assertForbidden();
    }

    public function test_404_for_a_nonexistent_clearance(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->post('/registrar/clearances/999999/grant-provisional', [
            'reason' => 'Test',
        ])->assertNotFound();
    }
}
