<?php

namespace Tests\Feature;

use App\Exceptions\EnrollmentException;
use App\Models\Clearance;
use App\Models\Program;
use App\Models\Section;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use App\Services\EnrollmentService;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollIrregularTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProgramSeeder::class);
        $this->program = Program::where('code', 'BSOA')->first();
        $this->user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '2nd Year']);
        Clearance::create([
            'user_id' => $this->user->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
        ]);
        // A failed grade makes the student irregular.
        StudentGrade::create(['user_id' => $this->user->id, 'subject_code' => 'ZZ999', 'status' => 'Failed']);
    }

    private function makeSubjectWithSection(string $code, array $sectionOverrides = []): array
    {
        $subject = Subject::factory()->for($this->program)->create(['code' => $code, 'year_level' => 1, 'semester' => 1]);
        $section = Section::factory()->for($subject)->create(array_merge(['school_year' => '2026-2027'], $sectionOverrides));

        return [$subject, $section];
    }

    public function test_catalogue_flags_missing_prerequisites_and_passed_subjects(): void
    {
        [$intro] = $this->makeSubjectWithSection('OA101');
        [$advance] = $this->makeSubjectWithSection('OA201');
        $advance->prerequisites()->attach($intro->id);
        [$done] = $this->makeSubjectWithSection('OA100');
        StudentGrade::create(['user_id' => $this->user->id, 'subject_code' => 'OA100', 'status' => 'Passed']);

        $catalogue = app(EnrollmentService::class)->catalogueFor($this->user)->keyBy('code');

        $this->assertTrue($catalogue['OA101']['eligible']);
        $this->assertFalse($catalogue['OA201']['eligible']);
        $this->assertStringContainsString('OA101', $catalogue['OA201']['reason']);
        $this->assertFalse($catalogue['OA100']['eligible']);
        $this->assertSame('Already passed', $catalogue['OA100']['reason']);
    }

    public function test_conflicting_sections_are_rejected(): void
    {
        [, $a] = $this->makeSubjectWithSection('OA101', ['days' => ['M'], 'start_time' => '08:00', 'end_time' => '10:00']);
        [, $b] = $this->makeSubjectWithSection('OA102', ['days' => ['M'], 'start_time' => '09:00', 'end_time' => '11:00']);

        $this->expectException(EnrollmentException::class);
        app(EnrollmentService::class)->enrollIrregular($this->user, [$a->id, $b->id]);
    }

    public function test_missing_prerequisite_is_rejected(): void
    {
        [$intro] = $this->makeSubjectWithSection('OA101');
        [$advance, $advSection] = $this->makeSubjectWithSection('OA201');
        $advance->prerequisites()->attach($intro->id);

        $this->expectException(EnrollmentException::class);
        app(EnrollmentService::class)->enrollIrregular($this->user, [$advSection->id]);
    }

    public function test_full_section_is_rejected(): void
    {
        [, $section] = $this->makeSubjectWithSection('OA101', ['capacity' => 1]);
        \App\Models\Enrollment::factory()->create(['status' => 'enrolled'])->sections()->attach($section->id);

        $this->expectException(EnrollmentException::class);
        app(EnrollmentService::class)->enrollIrregular($this->user, [$section->id]);
    }

    public function test_valid_picks_commit_as_pending(): void
    {
        [, $a] = $this->makeSubjectWithSection('OA101', ['days' => ['M'], 'start_time' => '08:00', 'end_time' => '09:00']);
        [, $b] = $this->makeSubjectWithSection('OA102', ['days' => ['T'], 'start_time' => '08:00', 'end_time' => '09:00']);

        $enrollment = app(EnrollmentService::class)->enrollIrregular($this->user, [$a->id, $b->id]);

        $this->assertSame('pending', $enrollment->status);
        $this->assertSame('irregular', $enrollment->type);
        $this->assertCount(2, $enrollment->sections);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Enrollment Submitted', 'target_id' => $enrollment->id]);
    }
}
