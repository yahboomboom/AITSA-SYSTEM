<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Setting;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransfereePlacementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        $program = Program::factory()->create(['code' => 'BSOA', 'years' => 4]);
        foreach ([[1, 1, 'Y1S1'], [1, 2, 'Y1S2'], [2, 1, 'Y2S1'], [2, 2, 'Y2S2'], [3, 1, 'Y3S1']] as [$year, $sem, $code]) {
            Subject::factory()->for($program)->create(['code' => $code, 'year_level' => $year, 'semester' => $sem]);
        }
    }

    private function student(string $type, string $year): User
    {
        return User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'applicant_type' => $type, 'year_level' => $year]);
    }

    private function pass(User $user, array $codes, ?string $source = 'credited'): void
    {
        foreach ($codes as $code) {
            StudentGrade::create(['user_id' => $user->id, 'subject_code' => $code, 'status' => 'Passed', 'source' => $source]);
        }
    }

    public function test_transferee_with_missing_lower_year_subjects_is_irregular(): void
    {
        $student = $this->student('TRANSFEREE', '2nd Year');
        $this->pass($student, ['Y1S1']);

        $this->assertTrue($student->isIrregularStudent());
        $this->assertSame(['Y1S2'], $student->backSubjectCodes());
    }

    public function test_transferee_becomes_regular_once_lower_years_are_complete(): void
    {
        $student = $this->student('RETURNEE', '2nd Year');
        $this->pass($student, ['Y1S1', 'Y1S2']);

        $this->assertFalse($student->isIrregularStudent());
    }

    public function test_in_second_semester_the_same_years_first_semester_counts_too(): void
    {
        Setting::put('semester', '2');
        $student = $this->student('TRANSFEREE', '2nd Year');
        $this->pass($student, ['Y1S1', 'Y1S2']);

        $this->assertSame(['Y2S1'], $student->backSubjectCodes());
        $this->assertTrue($student->isIrregularStudent());
    }

    public function test_continuing_students_are_unchanged(): void
    {
        // No grades on record (e.g. pre-system records) must not make a continuing student irregular.
        $this->assertFalse($this->student('NEW', '3rd Year')->isIrregularStudent());

        $failed = $this->student('NEW', '1st Year');
        StudentGrade::create(['user_id' => $failed->id, 'subject_code' => 'Y1S1', 'status' => 'Failed']);
        $this->assertTrue($failed->isIrregularStudent());
    }

    public function test_enrollment_page_sends_a_transferee_with_back_subjects_to_the_irregular_picker(): void
    {
        $student = $this->student('TRANSFEREE', '2nd Year');

        $this->actingAs($student)->getJson('/api/enrollment/context')->assertOk()
            ->assertJsonPath('student.type', 'irregular');
    }

    public function test_credits_overview_suggests_a_year_from_completed_years(): void
    {
        $student = $this->student('TRANSFEREE', '1st Year');
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->getJson("/registrar/students/{$student->id}/credits")
            ->assertJsonPath('placement.suggestedYear', '1st Year');

        $this->pass($student, ['Y1S1', 'Y1S2']);
        $this->actingAs($registrar)->getJson("/registrar/students/{$student->id}/credits")
            ->assertJsonPath('placement.suggestedYear', '2nd Year')
            ->assertJsonPath('placement.currentYear', '1st Year')
            ->assertJsonPath('placement.backSubjects', 0);

        // Year 2 complete too, but year 3 has a gap: suggest 3rd Year, never skip past a gap.
        $this->pass($student, ['Y2S1', 'Y2S2']);
        $this->actingAs($registrar)->getJson("/registrar/students/{$student->id}/credits")
            ->assertJsonPath('placement.suggestedYear', '3rd Year');
    }

    public function test_student_records_badge_shows_irregular_for_a_transferee_with_back_subjects(): void
    {
        $student = $this->student('TRANSFEREE', '2nd Year');

        $rows = collect($this->actingAs(User::factory()->create(['role' => 'registrar']))
            ->withSession(['auth.password_confirmed_at' => time()])
            ->getJson('/registrar/students/search?status=all')->json('rows'))->keyBy('id');

        $this->assertTrue($rows[$student->id]['isIrregular']);
    }
}
