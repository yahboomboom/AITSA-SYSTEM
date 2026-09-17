<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\Department;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentServiceClearanceGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocked_while_a_clearance_item_is_pending_or_on_hold(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $clearance = Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);
        $department = Department::factory()->create();
        $item = $clearance->items()->create(['department_id' => $department->id, 'status' => 'Pending']);

        $service = app(EnrollmentService::class);
        $this->assertFalse($service->clearanceComplete($student));

        $item->update(['status' => 'Hold']);
        $this->assertFalse($service->clearanceComplete($student->fresh()));

        $item->update(['status' => 'Approved']);
        $this->assertTrue($service->clearanceComplete($student->fresh()));
    }

    public function test_unaffected_when_student_has_zero_clearance_items(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);

        $this->assertTrue(app(EnrollmentService::class)->clearanceComplete($student));
    }

    public function test_provisional_clearance_passes_despite_pending_registrar_status(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Pending',
            'is_provisional' => true,
        ]);

        $this->assertTrue(app(EnrollmentService::class)->clearanceComplete($student));
    }

    public function test_non_provisional_clearance_still_blocked_on_pending_registrar_status(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Pending',
            'is_provisional' => false,
        ]);

        $this->assertFalse(app(EnrollmentService::class)->clearanceComplete($student));
    }

    public function test_provisional_clearance_does_not_override_an_active_registrar_hold(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Hold',
            'is_provisional' => true,
        ]);

        $this->assertFalse(app(EnrollmentService::class)->clearanceComplete($student));
    }

    public function test_down_payment_met_satisfies_the_cashier_leg_despite_pending_status(): void
    {
        $this->seed(\Database\Seeders\ProgramSeeder::class);
        \App\Models\Setting::put('down_payment_percent', '30');
        \App\Models\Setting::clearCache();
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        $program = \App\Models\Program::where('code', 'BSOA')->first();
        $subject = \App\Models\Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1, 'units' => 5]);
        \App\Models\Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Pending', 'registrar_status' => 'Approved',
        ]);
        // assessment = 1500 tuition + 1500 misc = 3000; 30% threshold = 900
        \App\Models\TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Settled', 'amount' => 900.00,
        ]);

        $this->assertTrue(app(EnrollmentService::class)->clearanceComplete($student));
    }

    public function test_neither_paid_enough_nor_waived_still_blocks(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Pending', 'registrar_status' => 'Approved',
        ]);

        $this->assertFalse(app(EnrollmentService::class)->clearanceComplete($student));
    }

    public function test_down_payment_waiver_satisfies_the_cashier_leg_even_with_zero_paid(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Pending', 'registrar_status' => 'Approved',
            'down_payment_waived' => true,
        ]);

        $this->assertTrue(app(EnrollmentService::class)->clearanceComplete($student));
    }

    public function test_down_payment_met_does_not_override_an_active_cashier_hold(): void
    {
        $this->seed(\Database\Seeders\ProgramSeeder::class);
        \App\Models\Setting::put('down_payment_percent', '30');
        \App\Models\Setting::clearCache();
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        $program = \App\Models\Program::where('code', 'BSOA')->first();
        $subject = \App\Models\Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1, 'units' => 5]);
        \App\Models\Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Hold', 'registrar_status' => 'Approved',
        ]);
        \App\Models\TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Settled', 'amount' => 900.00,
        ]);

        $this->assertFalse(app(EnrollmentService::class)->clearanceComplete($student));
    }

    public function test_down_payment_waiver_does_not_override_an_active_cashier_hold(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Clearance::create([
            'user_id' => $student->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Hold', 'registrar_status' => 'Approved',
            'down_payment_waived' => true,
        ]);

        $this->assertFalse(app(EnrollmentService::class)->clearanceComplete($student));
    }
}
