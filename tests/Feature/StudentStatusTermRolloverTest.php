<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The student header line ("Program · Year · Sem · Status") must follow the
 * Registrar's term rollover: new semester number, status back to Not Enrolled,
 * and the year level moving up only when a new school year starts.
 */
class StudentStatusTermRolloverTest extends TestCase
{
    use RefreshDatabase;

    private function headerLine(User $student): string
    {
        $html = $this->actingAs($student)->get('/dashboard')->assertOk()->getContent();
        $start = strpos($html, 'id="profileMenuWrap"');
        $button = substr($html, $start, strpos($html, '</button>', $start) - $start);

        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($button))));
    }

    private function startTerm(User $registrar, string $schoolYear, int $semester): void
    {
        $this->actingAs($registrar)->post('/registrar/start-new-term', [
            'school_year' => $schoolYear, 'semester' => $semester,
        ])->assertSessionHasNoErrors();
    }

    public function test_header_follows_the_student_through_two_term_rollovers(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '1');
        Program::factory()->create(['code' => 'BSOA', 'level' => 'bachelor', 'is_enrollable' => true, 'years' => 4]);
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create([
            'role' => 'student', 'name' => 'Dela Cruz, Juan', 'major' => 'BSOA', 'year_level' => '1st Year',
        ]);
        Clearance::initializeFor($student->id, '2026-2027', 1, ['admission_status' => 'Approved']);
        Enrollment::create(['user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1, 'type' => 'regular', 'status' => 'enrolled']);

        $this->assertStringContainsString('BSOA · 1st Year · Sem 1 · Enrolled', $this->headerLine($student));

        // Next semester, same school year: semester moves, status resets, year stays.
        $this->startTerm($registrar, '2026-2027', 2);
        $this->assertStringContainsString('BSOA · 1st Year · Sem 2 · Not Enrolled', $this->headerLine($student->fresh()));

        // New school year: year level moves up (they were enrolled in both semesters).
        Enrollment::create(['user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 2, 'type' => 'regular', 'status' => 'enrolled']);
        $this->startTerm($registrar, '2027-2028', 1);
        $this->assertStringContainsString('BSOA · 2nd Year · Sem 1 · Not Enrolled', $this->headerLine($student->fresh()));
    }

    public function test_going_back_to_an_earlier_school_year_does_not_promote_again(): void
    {
        Setting::put('school_year', '2026-2027');
        Setting::put('semester', '2');
        Program::factory()->create(['code' => 'BSOA', 'level' => 'bachelor', 'is_enrollable' => true, 'years' => 4]);
        $registrar = User::factory()->create(['role' => 'registrar']);
        $student = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        Enrollment::create(['user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 1, 'type' => 'regular', 'status' => 'enrolled']);
        Enrollment::create(['user_id' => $student->id, 'school_year' => '2026-2027', 'semester' => 2, 'type' => 'regular', 'status' => 'enrolled']);

        $this->startTerm($registrar, '2027-2028', 1);        // promoted to 2nd Year
        $this->startTerm($registrar, '2026-2027', 2);        // Registrar undoes a mistaken rollover

        $this->assertSame('2nd Year', $student->fresh()->year_level);
    }
}
