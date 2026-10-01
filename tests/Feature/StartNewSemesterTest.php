<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartNewSemesterTest extends TestCase
{
    use RefreshDatabase;

    private function collegeStudent(string $email): User
    {
        Program::factory()->create(['code' => 'BSOA', 'level' => 'bachelor', 'is_enrollable' => true]);

        return User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'email' => $email]);
    }

    private function tesdaStudent(string $email): User
    {
        Program::factory()->create(['code' => 'BK3', 'level' => 'tesda', 'is_enrollable' => false]);

        return User::factory()->create(['role' => 'student', 'major' => 'BK3', 'email' => $email]);
    }

    public function test_registrar_can_start_a_new_semester_for_college_students(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->collegeStudent('college-term-test@example.com');
        Clearance::initializeFor($student->id, '2026-2027', 1, ['chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved']);

        $response = $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => '2026-2027',
            'semester' => 2,
        ]);

        $response->assertRedirect();
        $this->assertSame('2', Setting::get('semester'));

        $newClearance = Clearance::where('user_id', $student->id)->where('semester', 2)->first();
        $this->assertNotNull($newClearance);
        $this->assertSame('Pending', $newClearance->chair_status);
        $this->assertSame('Pending', $newClearance->cashier_status);
        $this->assertSame('Pending', $newClearance->registrar_status);
        $this->assertFalse($newClearance->is_provisional);
        $this->assertDatabaseHas('audit_logs', ['action' => 'New Term Started']);
    }

    private function enroll(User $student, string $schoolYear, int $semester, string $status = 'enrolled'): void
    {
        Enrollment::create([
            'user_id' => $student->id, 'school_year' => $schoolYear, 'semester' => $semester,
            'type' => 'regular', 'status' => $status,
        ]);
    }

    private function rollToNextYear(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => '2027-2028',
            'semester' => 1,
        ])->assertRedirect();
    }

    public function test_student_who_skipped_a_semester_is_not_promoted(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '2');
        $student = $this->collegeStudent('skipped-sem2@example.com');
        $student->update(['year_level' => '1st Year']);
        $this->enroll($student, '2026-2027', 1);
        $other = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        $this->enroll($other, '2026-2027', 2);              // Sem 2 did run this year

        $this->rollToNextYear();

        $this->assertSame('1st Year', $student->fresh()->year_level);
        $this->assertDatabaseHas('audit_logs', ['action' => 'New Term Started']);
        $this->assertStringContainsString('2 student(s) kept at their year level',
            \App\Models\AuditLog::where('action', 'New Term Started')->value('description'));
    }

    public function test_student_who_never_enrolled_is_not_promoted(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '2');
        $student = $this->collegeStudent('never-enrolled@example.com');
        $student->update(['year_level' => '1st Year']);
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => '2027-2028',
            'semester' => 1,
        ])->assertSessionHas('success', fn ($msg) => str_contains($msg, '1 student(s) kept at their year level'));

        $this->assertSame('1st Year', $student->fresh()->year_level);
    }

    public function test_pending_or_rejected_enrollment_does_not_count(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '2');
        $student = $this->collegeStudent('pending-rejected@example.com');
        $student->update(['year_level' => '1st Year']);
        $this->enroll($student, '2026-2027', 1, 'pending');
        $this->enroll($student, '2026-2027', 2, 'rejected');

        $this->rollToNextYear();

        $this->assertSame('1st Year', $student->fresh()->year_level);
    }

    public function test_only_semesters_that_actually_ran_are_required(): void
    {
        // The school went straight from Sem 1 to the next school year — no Sem 2 ran.
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        $student = $this->collegeStudent('no-sem2-ran@example.com');
        $student->update(['year_level' => '1st Year']);
        $this->enroll($student, '2026-2027', 1);

        $this->rollToNextYear();

        $this->assertSame('2nd Year', $student->fresh()->year_level);
    }

    public function test_starting_a_new_school_year_advances_college_student_year_level(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '2');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->collegeStudent('promote-test@example.com');
        $student->update(['year_level' => '1st Year']);
        $this->enroll($student, '2026-2027', 1);
        $this->enroll($student, '2026-2027', 2);

        $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => '2027-2028',
            'semester' => 1,
        ])->assertRedirect();

        $this->assertSame('2nd Year', $student->fresh()->year_level);
    }

    public function test_starting_a_new_semester_within_the_same_school_year_does_not_advance_year_level(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = $this->collegeStudent('same-year-test@example.com');
        $student->update(['year_level' => '1st Year']);

        $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => '2026-2027',
            'semester' => 2,
        ])->assertRedirect();

        $this->assertSame('1st Year', $student->fresh()->year_level);
    }

    public function test_year_level_does_not_advance_past_the_programs_final_year(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '2');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);
        Program::factory()->create(['code' => 'FSM', 'level' => 'associate', 'years' => 2, 'is_enrollable' => true]);
        $student = User::factory()->create(['role' => 'student', 'major' => 'FSM', 'year_level' => '2nd Year']);
        $this->enroll($student, '2026-2027', 1);
        $this->enroll($student, '2026-2027', 2);

        $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => '2027-2028',
            'semester' => 1,
        ])->assertRedirect();

        $this->assertSame('2nd Year', $student->fresh()->year_level);
    }

    public function test_tesda_students_are_not_touched(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);
        $tesdaStudent = $this->tesdaStudent('tesda-term-test@example.com');
        $tesdaStudent->update(['year_level' => '1st Year']);
        Clearance::initializeFor($tesdaStudent->id, '2026-2027', 1);

        $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => '2027-2028',
            'semester' => 1,
        ]);

        $this->assertSame(1, Clearance::where('user_id', $tesdaStudent->id)->count());
        $this->assertSame('1st Year', $tesdaStudent->fresh()->year_level);
    }

    public function test_rejects_a_no_op_call_with_the_same_term(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Setting::clearCache();

        $registrar = User::factory()->create(['role' => 'registrar']);

        $response = $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => '2026-2027',
            'semester' => 1,
        ]);

        $response->assertSessionHasErrors();
        $this->assertSame('1', Setting::get('semester'));
    }

    public function test_admission_role_cannot_start_a_new_semester(): void
    {
        $admission = User::factory()->create(['role' => 'admission']);

        $this->actingAs($admission)->post('/registrar/start-new-term', [
            'school_year' => '2026-2027',
            'semester' => 2,
        ])->assertForbidden();
    }

    public function test_student_cannot_start_a_new_semester(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->post('/registrar/start-new-term', [
            'school_year' => '2026-2027',
            'semester' => 2,
        ])->assertForbidden();
    }
}
