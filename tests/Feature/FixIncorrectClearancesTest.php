<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class FixIncorrectClearancesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_reverts_the_current_terms_wrongly_approved_clearances_and_leaves_past_term_history_alone(): void
    {
        // A student with no program/enrollment and no settled payments always
        // has an unpaid balance under FeeAssessmentService's defaults, so
        // marking their clearance "Approved" is exactly the kind of mistake
        // this audit command is meant to catch and revert.
        $student = User::factory()->create(['role' => 'student', 'major' => null]);

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
        $pastClearance = Clearance::initializeFor($student->id, '2026-2027', 1, ['cashier_status' => 'Approved']);

        Setting::put('semester', '2');
        Setting::clearCache();
        $currentClearance = Clearance::initializeFor($student->id, '2026-2027', 2, ['cashier_status' => 'Approved']);

        Artisan::call('clearance:fix-incorrect');

        $pastClearance->refresh();
        $currentClearance->refresh();

        $this->assertSame('Approved', $pastClearance->cashier_status);
        $this->assertSame('Pending', $currentClearance->cashier_status);
    }
}
