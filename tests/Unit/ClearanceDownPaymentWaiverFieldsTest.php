<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceDownPaymentWaiverFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_down_payment_waiver_fields_are_mass_assignable_and_cast(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $cashier = User::factory()->create(['role' => 'cashier']);

        $clearance = Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Pending',
            'cashier_status' => 'Pending',
            'registrar_status' => 'Pending',
            'down_payment_waived' => true,
            'down_payment_waived_reason' => 'Guidance office endorsed hardship case.',
            'down_payment_waived_by' => $cashier->id,
            'down_payment_waived_at' => now(),
        ]);

        $fresh = $clearance->fresh();
        $this->assertTrue($fresh->down_payment_waived);
        $this->assertSame('Guidance office endorsed hardship case.', $fresh->down_payment_waived_reason);
        $this->assertSame($cashier->id, $fresh->down_payment_waived_by);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->down_payment_waived_at);
    }

    public function test_down_payment_waived_defaults_to_false(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $clearance = Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Pending',
            'cashier_status' => 'Pending',
            'registrar_status' => 'Pending',
        ]);

        $this->assertFalse($clearance->fresh()->down_payment_waived);
    }
}
