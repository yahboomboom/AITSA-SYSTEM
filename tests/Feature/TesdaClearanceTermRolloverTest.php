<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TesdaClearanceTermRolloverTest extends TestCase
{
    use RefreshDatabase;

    private function tesdaStudent(string $email): User
    {
        Program::factory()->create(['code' => 'BK3', 'level' => 'tesda', 'is_enrollable' => false]);

        return User::factory()->create([
            'role' => 'student',
            'major' => 'BK3',
            'program_level' => 'TESDA',
            'email' => $email,
        ]);
    }

    public function test_dashboard_returns_existing_approved_clearance_after_a_college_rollover_advances_the_term(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $student = $this->tesdaStudent('tesda-dashboard@example.com');
        $clearance = Clearance::initializeFor($student->id, '2026-2027', 1, ['cashier_status' => 'Approved']);

        // Simulate a College-only rollover having advanced the global term,
        // without actually running the rollover route (TESDA is never rolled).
        Setting::put('semester', '2');
        Setting::clearCache();

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('clearance', fn ($c) => $c->id === $clearance->id && $c->cashier_status === 'Approved');
        $this->assertSame(1, Clearance::where('user_id', $student->id)->count());
    }

    public function test_clearance_page_returns_existing_approved_clearance_after_a_college_rollover_advances_the_term(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $student = $this->tesdaStudent('tesda-clearance@example.com');
        $clearance = Clearance::initializeFor($student->id, '2026-2027', 1, ['cashier_status' => 'Approved']);

        Setting::put('semester', '2');
        Setting::clearCache();

        $response = $this->actingAs($student)->get('/clearance');

        $response->assertOk();
        $response->assertViewHas('clearance', fn ($c) => $c->id === $clearance->id && $c->cashier_status === 'Approved');
        $this->assertSame(1, Clearance::where('user_id', $student->id)->count());
    }

    public function test_cashier_hold_succeeds_against_a_tesda_students_pre_rollover_clearance(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $student = $this->tesdaStudent('tesda-hold@example.com');
        $clearance = Clearance::initializeFor($student->id, '2026-2027', 1, ['cashier_status' => 'Approved']);

        Setting::put('semester', '2');
        Setting::clearCache();

        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->post('/cashier/hold', [
            'user_id' => $student->id,
            'remarks' => 'Please settle outstanding balance.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $clearance->refresh();
        $this->assertSame('Hold', $clearance->cashier_status);
        $this->assertSame(1, Clearance::where('user_id', $student->id)->count());
    }

    public function test_new_tesda_student_with_no_clearance_still_gets_one_created_on_first_dashboard_visit(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $student = $this->tesdaStudent('tesda-brand-new@example.com');

        $this->assertSame(0, Clearance::where('user_id', $student->id)->count());

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertOk();
        $clearance = Clearance::where('user_id', $student->id)->first();
        $this->assertNotNull($clearance);
        $this->assertSame('2026-2027', $clearance->school_year);
        $this->assertSame(1, $clearance->semester);
    }
}
