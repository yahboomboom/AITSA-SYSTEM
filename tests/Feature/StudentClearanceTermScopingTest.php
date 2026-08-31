<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentClearanceTermScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_initializes_clearance_for_the_current_term(): void
    {
        Setting::put('school_year', '2027-2028');
        Setting::put('semester', '2');
        Setting::clearCache();

        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/dashboard')->assertOk();

        $clearance = Clearance::where('user_id', $student->id)->first();
        $this->assertSame('2027-2028', $clearance->school_year);
        $this->assertSame(2, $clearance->semester);
    }

    public function test_enrollment_page_reads_the_current_terms_clearance_not_a_past_one(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
        $past = Clearance::initializeFor($student->id, '2026-2027', 1, ['registrar_status' => 'Approved']);

        Setting::put('semester', '2');
        Setting::clearCache();
        Clearance::initializeFor($student->id, '2026-2027', 2, ['registrar_status' => 'Pending']);

        $response = $this->actingAs($student)->get('/enrollment');

        $response->assertOk();
        $response->assertViewHas('clearance', function ($clearance) use ($past) {
            return $clearance->id !== $past->id && $clearance->registrar_status === 'Pending';
        });
    }

    public function test_ledger_page_reads_the_current_terms_clearance(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();
        $clearance = Clearance::initializeFor($student->id, '2026-2027', 1);

        $response = $this->actingAs($student)->get('/ledger');

        $response->assertOk();
        $response->assertViewHas('clearance', fn ($c) => $c->id === $clearance->id);
    }
}
