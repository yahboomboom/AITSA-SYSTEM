<?php

namespace Tests\Feature;

use App\Models\AdmissionSlotLimit;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\User;
use App\Services\AdmissionWithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionWithdrawalRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function reservedStudent(int $daysAgo = 20, array $attrs = []): User
    {
        $student = User::factory()->create(array_merge([
            'role' => 'student', 'is_reserved' => true, 'program_key' => 'bsoa',
        ], $attrs));
        $this->travel(-$daysAgo)->days();
        Clearance::create([
            'user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1,
            'admission_status' => 'Approved', 'chair_status' => 'Pending',
            'cashier_status' => 'Pending', 'registrar_status' => 'Pending',
        ]);
        $this->travelBack();

        return $student;
    }

    public function test_registrar_bulk_withdraws_and_skips_ineligible(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $a = $this->reservedStudent();
        $b = $this->reservedStudent();
        $enrolling = $this->reservedStudent(20, ['name' => 'Santos, Ana']);
        Enrollment::create(['user_id' => $enrolling->id, 'school_year' => '2026-2027', 'semester' => 1, 'type' => 'regular', 'status' => 'pending']);

        $response = $this->actingAs($registrar)->post('/registrar/no-shows/withdraw', [
            'ids' => [$a->id, $b->id, $enrolling->id],
            'reason' => 'no_show',
            'note' => 'Called twice',
        ]);

        $response->assertRedirect(route('registrar.dashboard'));
        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, '2 student') && str_contains($msg, 'Santos, Ana'));
        $this->assertSame('withdrawn', $a->fresh()->role);
        $this->assertSame('withdrawn', $b->fresh()->role);
        $this->assertSame('student', $enrolling->fresh()->role);
    }

    public function test_withdraw_validates_reason_and_ids(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->reservedStudent();

        $this->actingAs($registrar)->post('/registrar/no-shows/withdraw', ['ids' => [$student->id], 'reason' => 'bogus'])
            ->assertSessionHasErrors('reason');
        $this->actingAs($registrar)->post('/registrar/no-shows/withdraw', ['ids' => [], 'reason' => 'no_show'])
            ->assertSessionHasErrors('ids');
        $this->assertSame('student', $student->fresh()->role);
    }

    public function test_when_nothing_is_eligible_an_error_is_flashed(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create(['role' => 'applicant']);

        $this->actingAs($registrar)->post('/registrar/no-shows/withdraw', ['ids' => [$applicant->id], 'reason' => 'no_show'])
            ->assertSessionHas('error');
    }

    public function test_threshold_route_updates_setting(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->post('/registrar/no-shows/threshold', ['days' => 30])->assertRedirect();
        $this->assertSame('30', Setting::get('no_show_after_days'));

        $this->actingAs($registrar)->post('/registrar/no-shows/threshold', ['days' => 0])->assertSessionHasErrors('days');

        $this->actingAs($registrar)->post('/registrar/no-shows/threshold', ['days' => 1])
            ->assertSessionHas('success', 'Students are now flagged as possible no-shows after 1 day.');
    }

    public function test_reinstate_route(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->reservedStudent();
        app(AdmissionWithdrawalService::class)->withdraw($student, 'no_show', null, $registrar);

        $this->actingAs($registrar)->post("/registrar/withdrawn/{$student->id}/reinstate")
            ->assertSessionHas('success');
        $this->assertSame('student', $student->fresh()->role);
    }

    public function test_reinstate_route_flashes_error_when_full(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->reservedStudent();
        app(AdmissionWithdrawalService::class)->withdraw($student, 'no_show', null, $registrar);
        AdmissionSlotLimit::forProgram('bsoa', 'BSOA', '2026-2027')->update(['total_slots' => 0]);

        $this->actingAs($registrar)->post("/registrar/withdrawn/{$student->id}/reinstate")
            ->assertSessionHas('error');
        $this->assertSame('withdrawn', $student->fresh()->role);
    }

    public function test_non_registrars_are_forbidden(): void
    {
        $student = $this->reservedStudent();
        foreach (['admission', 'cashier', 'chair', 'admin', 'student'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->post('/registrar/no-shows/withdraw', ['ids' => [$student->id], 'reason' => 'no_show'])->assertForbidden();
            $this->actingAs($user)->post('/registrar/no-shows/threshold', ['days' => 5])->assertForbidden();
            $this->actingAs($user)->post("/registrar/withdrawn/{$student->id}/reinstate")->assertForbidden();
        }
        $this->assertSame('student', $student->fresh()->role);
    }

    public function test_withdrawn_student_cannot_log_in(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->reservedStudent(20, ['login_id' => '2026-00777', 'password' => 'right-password']);
        app(AdmissionWithdrawalService::class)->withdraw($student, 'no_show', null, $registrar);

        $response = $this->post('/login', ['login_id' => '2026-00777', 'password' => 'right-password']);

        $response->assertSessionHasErrors(['login_id' => "Your admission was withdrawn. Please contact the Registrar's office."]);
        $this->assertGuest();
    }

    public function test_withdrawn_student_is_hidden_from_operational_queues(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->reservedStudent(20, ['name' => 'Withdrawnperson, Zed']);
        app(AdmissionWithdrawalService::class)->withdraw($student, 'no_show', null, $registrar);

        $chair = User::factory()->create(['role' => 'chair']);
        $cashier = User::factory()->create(['role' => 'cashier']);

        // The name still appears in the "withdrawn" context list, never as a clearance row.
        $this->actingAs($registrar)->get('/registrar/dashboard')
            ->assertDontSee('&quot;studentName&quot;:&quot;Withdrawnperson, Zed&quot;', false);
        $this->actingAs($chair)->get('/approver/dashboard')->assertDontSee('Withdrawnperson, Zed');
        $this->actingAs($cashier)->get(route('cashier.dashboard'))->assertDontSee('Withdrawnperson, Zed');
        $this->actingAs($cashier)->get(route('cashier.accounts'))->assertDontSee('Withdrawnperson, Zed');

        $department = \App\Models\Department::create(['name' => 'Library', 'code' => 'LIB', 'is_active' => true]);
        \App\Models\ClearanceItem::create([
            'clearance_id' => Clearance::where('user_id', $student->id)->value('id'),
            'department_id' => $department->id,
            'status' => 'Pending',
        ]);
        $officer = User::factory()->create(['role' => 'department_officer', 'department_id' => $department->id]);
        $this->actingAs($officer)->get('/department/dashboard')->assertDontSee('Withdrawnperson, Zed');
    }

    public function test_an_open_session_is_ended_once_the_student_is_withdrawn(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->reservedStudent();
        $this->actingAs($student);
        app(AdmissionWithdrawalService::class)->withdraw($student, 'no_show', null, $registrar);

        $this->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login_id' => "Your admission was withdrawn. Please contact the Registrar's office."]);
        $this->assertGuest();
    }

    public function test_dashboard_context_lists_no_shows_and_withdrawn_for_registrar_only(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $admission = User::factory()->create(['role' => 'admission']);
        $this->reservedStudent(20, ['name' => 'Flagged, Fe']);
        $gone = $this->reservedStudent(20, ['name' => 'Gone, Gil']);
        app(AdmissionWithdrawalService::class)->withdraw($gone, 'withdrew', 'Moved', $registrar);

        // Flagged student's name also appears in the clearance queue, so assert on
        // the no-show-only keys instead of the name.
        $this->actingAs($registrar)->get('/registrar/dashboard')
            ->assertSee('&quot;canManageWithdrawals&quot;:true', false)
            ->assertSee('&quot;reservedOnFormatted&quot;', false)
            ->assertSee('Withdrew (informed us)');

        $this->actingAs($admission)->get('/registrar/dashboard')
            ->assertSee('&quot;canManageWithdrawals&quot;:false', false)
            ->assertSee('&quot;noShows&quot;:[]', false)
            ->assertSee('&quot;withdrawn&quot;:[]', false);
    }
}
