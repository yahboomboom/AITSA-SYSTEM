<?php

namespace Tests\Feature;

use App\Models\AdmissionSlotLimit;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\TransactionLedger;
use App\Models\User;
use App\Services\AdmissionWithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionWithdrawalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_key_and_withdrawal_fields_are_mass_assignable(): void
    {
        $user = User::create([
            'name' => 'Dela Cruz, Juan',
            'login_id' => '2026-00001',
            'email' => 'juan@example.com',
            'password' => 'secret-password',
            'role' => 'student',
            'program_key' => 'bsoa',
            'withdrawn_at' => now(),
            'withdrawal_reason' => 'no_show',
            'withdrawal_note' => 'No reply to calls',
        ]);

        $fresh = $user->fresh();
        $this->assertSame('bsoa', $fresh->program_key);
        $this->assertSame('no_show', $fresh->withdrawal_reason);
        $this->assertSame('No reply to calls', $fresh->withdrawal_note);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->withdrawn_at);
    }

    private function reservedStudent(int $daysAgo, array $attrs = []): User
    {
        $student = User::factory()->create(array_merge([
            'role' => 'student',
            'is_reserved' => true,
            'program_key' => 'bsoa',
        ], $attrs));

        $this->travel(-$daysAgo)->days();
        Clearance::create([
            'user_id' => $student->id,
            'school_year' => '2026-2027',
            'semester' => 1,
            'admission_status' => 'Approved',
            'chair_status' => 'Pending',
            'cashier_status' => 'Pending',
            'registrar_status' => 'Pending',
        ]);
        $this->travelBack();

        return $student;
    }

    private function service(): AdmissionWithdrawalService
    {
        return app(AdmissionWithdrawalService::class);
    }

    public function test_flags_reserved_students_with_no_enrollment_older_than_threshold(): void
    {
        $old = $this->reservedStudent(20);
        $this->reservedStudent(5);                                   // too recent
        $notReserved = $this->reservedStudent(20, ['is_reserved' => false]);
        $pending = $this->reservedStudent(20);
        Enrollment::create(['user_id' => $pending->id, 'school_year' => '2026-2027', 'semester' => 1, 'type' => 'regular', 'status' => 'pending']);
        $enrolled = $this->reservedStudent(20);
        Enrollment::create(['user_id' => $enrolled->id, 'school_year' => '2026-2027', 'semester' => 1, 'type' => 'regular', 'status' => 'enrolled']);
        $rejected = $this->reservedStudent(20);                     // rejected enrollment still counts as "not continuing"
        Enrollment::create(['user_id' => $rejected->id, 'school_year' => '2026-2027', 'semester' => 1, 'type' => 'regular', 'status' => 'rejected']);

        $ids = $this->service()->possibleNoShows()->pluck('user_id')->sort()->values()->all();

        $this->assertSame(collect([$old->id, $rejected->id])->sort()->values()->all(), $ids);
        $this->assertNotContains($notReserved->id, $ids);
    }

    public function test_threshold_setting_is_respected(): void
    {
        $student = $this->reservedStudent(5);
        $this->assertCount(0, $this->service()->possibleNoShows());

        Setting::put('no_show_after_days', '3');

        $this->assertSame([$student->id], $this->service()->possibleNoShows()->pluck('user_id')->all());
    }

    public function test_withdraw_frees_the_slot_and_keeps_the_record(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $this->actingAs($registrar);
        $student = $this->reservedStudent(20);
        TransactionLedger::create([
            'user_id' => $student->id, 'gateway' => 'counter', 'amount' => 500,
            'fee_type' => 'reservation', 'status' => 'Paid', 'reference_no' => 'RES-KEEP',
        ]);
        $limit = AdmissionSlotLimit::forProgram('bsoa', 'BSOA', '2026-2027');
        $this->assertSame(1, $limit->takenCount());

        $ok = $this->service()->withdraw($student, 'no_show', 'No reply', $registrar);

        $this->assertTrue($ok);
        $student->refresh();
        $this->assertSame('withdrawn', $student->role);
        $this->assertFalse($student->is_reserved);
        $this->assertSame('no_show', $student->withdrawal_reason);
        $this->assertSame('No reply', $student->withdrawal_note);
        $this->assertSame($registrar->id, $student->withdrawn_by);
        $this->assertNotNull($student->withdrawn_at);
        $this->assertNull($student->deleted_at);
        $this->assertSame('Withdrawn', Clearance::where('user_id', $student->id)->value('admission_status'));
        $this->assertSame(0, $limit->takenCount());
        $this->assertDatabaseHas('transaction_ledgers', ['reference_no' => 'RES-KEEP', 'status' => 'Paid', 'amount' => 500]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Admission Withdrawn', 'target_id' => $student->id]);
    }

    public function test_withdraw_refuses_a_student_who_started_enrolling(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->reservedStudent(20);
        Enrollment::create(['user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1, 'type' => 'regular', 'status' => 'pending']);

        $this->assertFalse($this->service()->withdraw($student, 'no_show', null, $registrar));
        $this->assertSame('student', $student->fresh()->role);
        $this->assertTrue($student->fresh()->is_reserved);
    }

    public function test_withdraw_refuses_non_students_and_unreserved(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $applicant = User::factory()->create(['role' => 'applicant', 'is_reserved' => false]);
        $unreserved = $this->reservedStudent(20, ['is_reserved' => false]);

        $this->assertFalse($this->service()->withdraw($applicant, 'no_show', null, $registrar));
        $this->assertFalse($this->service()->withdraw($unreserved, 'no_show', null, $registrar));
        $this->assertSame('applicant', $applicant->fresh()->role);
        $this->assertSame('student', $unreserved->fresh()->role);
    }

    public function test_reinstate_restores_the_student(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $this->actingAs($registrar);
        $student = $this->reservedStudent(20);
        $this->service()->withdraw($student, 'withdrew', 'Moved away', $registrar);

        $this->service()->reinstate($student->fresh(), $registrar);

        $student->refresh();
        $this->assertSame('student', $student->role);
        $this->assertTrue($student->is_reserved);
        $this->assertNull($student->withdrawn_at);
        $this->assertNull($student->withdrawal_reason);
        $this->assertNull($student->withdrawal_note);
        $this->assertNull($student->withdrawn_by);
        $this->assertSame('Approved', Clearance::where('user_id', $student->id)->value('admission_status'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'Admission Reinstated', 'target_id' => $student->id]);
    }

    public function test_reinstate_is_refused_when_the_program_is_full(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->reservedStudent(20);
        $this->service()->withdraw($student, 'no_show', null, $registrar);
        AdmissionSlotLimit::forProgram('bsoa', 'BSOA', '2026-2027')->update(['total_slots' => 1]);
        User::factory()->create(['role' => 'student', 'is_reserved' => true, 'program_key' => 'bsoa']);

        try {
            $this->service()->reinstate($student->fresh(), $registrar);
            $this->fail('Expected DomainException');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('no free slot', $e->getMessage());
        }

        $this->assertSame('withdrawn', $student->fresh()->role);
    }

    public function test_reinstate_is_refused_for_a_student_who_is_not_withdrawn(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->reservedStudent(20);

        $this->expectException(\DomainException::class);
        $this->service()->reinstate($student, $registrar);
    }

    public function test_excluding_withdrawn_scope(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $kept = $this->reservedStudent(20);
        $gone = $this->reservedStudent(20);
        $this->service()->withdraw($gone, 'no_show', null, $registrar);

        $ids = Clearance::excludingWithdrawn()->pluck('user_id')->all();

        $this->assertContains($kept->id, $ids);
        $this->assertNotContains($gone->id, $ids);
    }

    public function test_continuing_students_are_never_flagged_or_withdrawable(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        // A 2nd-term student: previous-term clearance + an enrolled enrollment, then a
        // fresh current-term clearance from the term rollover, no enrollment yet.
        $continuing = User::factory()->create(['role' => 'student', 'is_reserved' => true, 'program_key' => 'bsoa']);
        Clearance::create(['user_id' => $continuing->id, 'school_year' => '2025-2026', 'semester' => 2, 'admission_status' => 'Approved']);
        Enrollment::create(['user_id' => $continuing->id, 'school_year' => '2025-2026', 'semester' => 2, 'type' => 'regular', 'status' => 'enrolled']);
        $this->travel(-20)->days();
        Clearance::create(['user_id' => $continuing->id, 'school_year' => '2026-2027', 'semester' => 1, 'admission_status' => 'Approved']);
        $this->travelBack();

        $this->assertNotContains($continuing->id, $this->service()->possibleNoShows()->pluck('user_id')->all());
        $this->assertFalse($this->service()->withdraw($continuing, 'no_show', null, $registrar));
        $this->assertSame('student', $continuing->fresh()->role);
    }

    public function test_slot_count_only_includes_this_school_years_admission_cohort(): void
    {
        $this->reservedStudent(20);                                  // this year's admit
        $older = User::factory()->create(['role' => 'student', 'is_reserved' => true, 'program_key' => 'bsoa']);
        Clearance::create(['user_id' => $older->id, 'school_year' => '2025-2026', 'semester' => 1, 'admission_status' => 'Approved']);
        Clearance::create(['user_id' => $older->id, 'school_year' => '2026-2027', 'semester' => 1, 'admission_status' => 'Approved']);

        $this->assertSame(1, AdmissionSlotLimit::forProgram('bsoa', 'BSOA', '2026-2027')->takenCount());
    }

    public function test_backfill_migration_sets_program_key_from_major(): void
    {
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA']);
        \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['program_key' => null]);

        (require database_path('migrations/2026_10_01_000002_backfill_program_key_on_users.php'))->up();

        $this->assertSame('bsoa', $user->fresh()->program_key);
    }
}
