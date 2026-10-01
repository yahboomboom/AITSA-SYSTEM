<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Registrar can correct a College student's year level from Student
 * Records — with a required reason, an audit trail, the program's length as
 * the ceiling, and never while the student is mid-enrollment this term.
 */
class RegistrarYearLevelEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Program::factory()->create(['code' => 'BSOA', 'level' => 'bachelor', 'years' => 4, 'is_enrollable' => true]);
        Program::factory()->create(['code' => 'FSM', 'level' => 'associate', 'years' => 2, 'is_enrollable' => true]);
        Program::factory()->create(['code' => 'BK3', 'level' => 'tesda', 'is_enrollable' => false]);
    }

    private function registrar(): User
    {
        return User::factory()->create(['role' => 'registrar']);
    }

    private function student(string $major = 'BSOA', string $year = '1st Year'): User
    {
        return User::factory()->create(['role' => 'student', 'major' => $major, 'year_level' => $year, 'name' => 'Dela Cruz, Juan']);
    }

    private function change(User $actor, User $student, array $data)
    {
        return $this->actingAs($actor)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson("/registrar/students/{$student->id}/year-level", $data);
    }

    public function test_registrar_changes_year_level_with_a_reason_and_it_is_audited(): void
    {
        $student = $this->student();

        $this->change($this->registrar(), $student, ['year_level' => '2nd Year', 'reason' => 'Paper records migration'])
            ->assertOk()
            ->assertJson(['yearLevel' => '2nd Year']);

        $this->assertSame('2nd Year', $student->fresh()->year_level);
        $log = \App\Models\AuditLog::where('action', 'Year Level Changed')->where('target_id', $student->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('1st Year → 2nd Year', $log->description);
        $this->assertStringContainsString('Paper records migration', $log->description);
    }

    public function test_reason_is_required(): void
    {
        $student = $this->student();

        $this->change($this->registrar(), $student, ['year_level' => '2nd Year', 'reason' => ''])
            ->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->assertSame('1st Year', $student->fresh()->year_level);
    }

    public function test_year_level_cannot_exceed_the_programs_length(): void
    {
        $student = $this->student('FSM', '2nd Year');

        $this->change($this->registrar(), $student, ['year_level' => '3rd Year', 'reason' => 'Typo'])
            ->assertStatus(422)->assertJsonFragment(['message' => 'FSM only runs for 2 years.']);
        $this->assertSame('2nd Year', $student->fresh()->year_level);
    }

    public function test_tesda_students_have_no_year_level_to_edit(): void
    {
        $student = $this->student('BK3');

        $this->change($this->registrar(), $student, ['year_level' => '2nd Year', 'reason' => 'x'])
            ->assertStatus(422)->assertJsonFragment(['message' => 'Year level only applies to College programs.']);
    }

    public function test_blocked_while_the_student_is_enrolling_this_term(): void
    {
        foreach (['pending', 'enrolled'] as $status) {
            $student = $this->student();
            Enrollment::create(['user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1, 'type' => 'regular', 'status' => $status]);

            $this->change($this->registrar(), $student, ['year_level' => '2nd Year', 'reason' => 'x'])
                ->assertStatus(422)
                ->assertJsonFragment(['message' => 'This student has a ' . $status . ' enrollment this term. Change the year level after this term, or reject their enrollment first.']);
            $this->assertSame('1st Year', $student->fresh()->year_level);
        }
    }

    public function test_a_rejected_or_earlier_term_enrollment_does_not_block(): void
    {
        $student = $this->student();
        Enrollment::create(['user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1, 'type' => 'regular', 'status' => 'rejected']);
        Enrollment::create(['user_id' => $student->id, 'school_year' => '2025-2026', 'semester' => 2, 'type' => 'regular', 'status' => 'enrolled']);

        $this->change($this->registrar(), $student, ['year_level' => '2nd Year', 'reason' => 'x'])->assertOk();
    }

    public function test_same_year_level_is_rejected(): void
    {
        $student = $this->student();

        $this->change($this->registrar(), $student, ['year_level' => '1st Year', 'reason' => 'x'])
            ->assertStatus(422)->assertJsonFragment(['message' => 'Dela Cruz, Juan is already 1st Year.']);
    }

    public function test_invalid_year_value_is_rejected(): void
    {
        $this->change($this->registrar(), $this->student(), ['year_level' => '5th Year', 'reason' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('year_level');
    }

    public function test_only_the_registrar_can_change_year_level(): void
    {
        $student = $this->student();
        foreach (['admission', 'cashier', 'chair', 'admin', 'student'] as $role) {
            $this->change(User::factory()->create(['role' => $role]), $student, ['year_level' => '2nd Year', 'reason' => 'x'])
                ->assertForbidden();
        }
        $this->assertSame('1st Year', $student->fresh()->year_level);
    }

    public function test_target_must_be_a_student(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->change($this->registrar(), $cashier, ['year_level' => '2nd Year', 'reason' => 'x'])->assertNotFound();
    }

    public function test_student_records_rows_carry_edit_info_for_the_registrar_only(): void
    {
        $this->student('FSM', '1st Year');
        $this->student('BK3', '1st Year')->update(['name' => 'Tesda, Tina']);

        $rows = $this->actingAs($this->registrar())->withSession(['auth.password_confirmed_at' => time()])
            ->getJson('/registrar/students/search?status=all')->assertOk()->json('rows');
        $fsm = collect($rows)->firstWhere('major', 'FSM');
        $tesda = collect($rows)->firstWhere('major', 'BK3');
        $this->assertSame(['1st Year', '2nd Year'], $fsm['yearEdit']['options']);
        $this->assertStringEndsWith("/registrar/students/{$fsm['id']}/year-level", $fsm['yearEdit']['url']);
        $this->assertNull($tesda['yearEdit']);

        $admissionRows = $this->actingAs(User::factory()->create(['role' => 'admission']))
            ->withSession(['auth.password_confirmed_at' => time()])
            ->getJson('/registrar/students/search?status=all')->json('rows');
        $this->assertNull(collect($admissionRows)->firstWhere('major', 'FSM')['yearEdit']);
    }
}
