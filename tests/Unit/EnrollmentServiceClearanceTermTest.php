<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\Setting;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentServiceClearanceTermTest extends TestCase
{
    use RefreshDatabase;

    public function test_clearance_complete_ignores_a_fully_approved_clearance_from_a_past_term(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 1, [
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);

        Setting::put('semester', '2');
        Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 2, [
            'chair_status' => 'Pending', 'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
        ]);

        $this->assertFalse((new EnrollmentService())->clearanceComplete($student));
    }

    public function test_clearance_complete_is_true_when_the_current_terms_clearance_is_approved(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 1, [
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);

        $this->assertTrue((new EnrollmentService())->clearanceComplete($student));
    }
}
