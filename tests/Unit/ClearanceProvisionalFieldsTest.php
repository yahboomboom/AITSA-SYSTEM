<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceProvisionalFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisional_fields_are_mass_assignable_and_cast(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $registrar = User::factory()->create(['role' => 'registrar']);

        $clearance = Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Pending',
            'cashier_status' => 'Pending',
            'registrar_status' => 'Pending',
            'is_provisional' => true,
            'provisional_reason' => 'Form 137 still with previous school.',
            'provisional_granted_by' => $registrar->id,
            'provisional_granted_at' => now(),
        ]);

        $fresh = $clearance->fresh();
        $this->assertTrue($fresh->is_provisional);
        $this->assertSame('Form 137 still with previous school.', $fresh->provisional_reason);
        $this->assertSame($registrar->id, $fresh->provisional_granted_by);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->provisional_granted_at);
    }

    public function test_is_provisional_defaults_to_false(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $clearance = Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Pending',
            'cashier_status' => 'Pending',
            'registrar_status' => 'Pending',
        ]);

        $this->assertFalse($clearance->fresh()->is_provisional);
    }
}
