<?php

namespace Tests\Feature;

use App\Exceptions\EnrollmentException;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Services\MatriculationChangeService;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Irregular students may take a subject from any year level once its
 * prerequisites are passed, and every self-built load (enrollment or a
 * change of matriculation) is capped at the Registrar's max units per term.
 */
class IrregularEnrollmentRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProgramSeeder::class);
        $this->program = Program::where('code', 'BSOA')->first();
        $this->user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        Clearance::create([
            'user_id' => $this->user->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
        ]);
        StudentGrade::create(['user_id' => $this->user->id, 'subject_code' => 'ZZ999', 'status' => 'Failed']);
    }

    /** One subject + section; sections are spread across days so they never conflict. */
    private function subject(string $code, int $year = 1, int $units = 3, ?string $day = null): array
    {
        static $days = ['M', 'T', 'W', 'Th', 'F', 'S'];
        static $i = 0;
        $subject = Subject::factory()->for($this->program)->create([
            'code' => $code, 'year_level' => $year, 'semester' => 1, 'units' => $units,
        ]);
        $section = Section::factory()->for($subject)->create([
            'school_year' => '2026-2027', 'days' => [$day ?? $days[$i++ % 6]],
            'start_time' => sprintf('%02d:00', 7 + intdiv($i, 6) * 2), 'end_time' => sprintf('%02d:30', 8 + intdiv($i, 6) * 2),
        ]);

        return [$subject, $section];
    }

    private function service(): EnrollmentService
    {
        return app(EnrollmentService::class);
    }

    public function test_catalogue_offers_higher_year_subjects_gated_only_by_prerequisites(): void
    {
        [$intro] = $this->subject('OA101', 1);
        [$open] = $this->subject('OA301', 3);                 // no prerequisite
        [$locked] = $this->subject('OA302', 3);
        $locked->prerequisites()->attach($intro->id);         // OA101 not passed yet

        $catalogue = $this->service()->catalogueFor($this->user)->keyBy('code');

        $this->assertTrue($catalogue['OA301']['eligible']);
        $this->assertFalse($catalogue['OA302']['eligible']);
        $this->assertStringContainsString('OA101', $catalogue['OA302']['reason']);
    }

    public function test_catalogue_lists_each_subjects_prerequisite_codes(): void
    {
        [$a] = $this->subject('OA101', 1);
        [$b] = $this->subject('OA102', 1);
        [$advanced] = $this->subject('OA302', 3);
        $advanced->prerequisites()->attach([$a->id, $b->id]);

        $catalogue = $this->service()->catalogueFor($this->user)->keyBy('code');

        $this->assertSame(['OA101', 'OA102'], $catalogue['OA302']['prerequisites']);
        $this->assertSame([], $catalogue['OA101']['prerequisites']);
    }

    public function test_irregular_student_can_enroll_in_a_higher_year_subject_once_prerequisites_are_passed(): void
    {
        [$intro] = $this->subject('OA101', 1);
        [$advanced, $section] = $this->subject('OA302', 3);
        $advanced->prerequisites()->attach($intro->id);
        StudentGrade::create(['user_id' => $this->user->id, 'subject_code' => 'OA101', 'status' => 'Passed']);

        $enrollment = $this->service()->enrollIrregular($this->user, [$section->id]);

        $this->assertSame('pending', $enrollment->status);
    }

    public function test_irregular_load_over_the_unit_cap_is_rejected(): void
    {
        Setting::put('max_units_per_term', '6');
        [, $a] = $this->subject('OA101', 1, 3);
        [, $b] = $this->subject('OA102', 1, 3);
        [, $c] = $this->subject('OA103', 1, 3);

        try {
            $this->service()->enrollIrregular($this->user, [$a->id, $b->id, $c->id]);
            $this->fail('Expected EnrollmentException');
        } catch (EnrollmentException $e) {
            $this->assertSame('This selection is 9 units; the maximum is 6 units per term.', $e->getMessage());
        }

        $this->assertSame('pending', $this->service()->enrollIrregular($this->user, [$a->id, $b->id])->status);
    }

    public function test_default_unit_cap_is_26(): void
    {
        $sections = [];
        foreach (range(1, 9) as $n) {
            $sections[] = $this->subject('OA1' . str_pad((string) $n, 2, '0', STR_PAD_LEFT), 1, 3)[1]->id;   // 27 units
        }

        $this->expectExceptionMessage('maximum is 26 units');
        $this->service()->enrollIrregular($this->user, $sections);
    }

    public function test_enrollment_context_reports_the_unit_cap(): void
    {
        Setting::put('max_units_per_term', '21');

        $this->actingAs($this->user)->getJson('/api/enrollment/context')
            ->assertOk()->assertJsonPath('max_units', 21);
    }

    public function test_change_of_matriculation_cannot_push_the_load_over_the_cap(): void
    {
        Setting::put('max_units_per_term', '6');
        Setting::put('change_matriculation_open', '1');
        [, $a] = $this->subject('OA101', 1, 3);
        [, $b] = $this->subject('OA102', 1, 3);
        [, $extra] = $this->subject('OA103', 1, 3);
        $enrollment = Enrollment::create(['user_id' => $this->user->id, 'school_year' => '2026-2027', 'semester' => 1, 'type' => 'irregular', 'status' => 'enrolled']);
        $enrollment->sections()->attach([$a->id, $b->id]);

        $this->expectExceptionMessage('This change would make your load 9 units; the maximum is 6 units per term.');
        app(MatriculationChangeService::class)->submit($this->user, [['action' => 'add', 'section_id' => $extra->id]]);
    }

    public function test_change_of_matriculation_still_allows_a_drop_when_already_over_the_cap(): void
    {
        // A regular block can exceed the cap; dropping or swapping must still work.
        Setting::put('max_units_per_term', '3');
        Setting::put('change_matriculation_open', '1');
        [, $a] = $this->subject('OA101', 1, 3);
        [, $b] = $this->subject('OA102', 1, 3);
        $enrollment = Enrollment::create(['user_id' => $this->user->id, 'school_year' => '2026-2027', 'semester' => 1, 'type' => 'regular', 'status' => 'enrolled']);
        $enrollment->sections()->attach([$a->id, $b->id]);

        $change = app(MatriculationChangeService::class)->submit($this->user, [['action' => 'drop', 'section_id' => $b->id]]);

        $this->assertSame('pending', $change->status);
    }

    public function test_registrar_sets_the_unit_cap(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        $this->actingAs($registrar)->post('/registrar/max-units', ['max_units' => 24])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('24', Setting::get('max_units_per_term'));

        $this->actingAs($registrar)->post('/registrar/max-units', ['max_units' => 2])->assertSessionHasErrors('max_units');
        $this->actingAs($registrar)->post('/registrar/max-units', ['max_units' => 60])->assertSessionHasErrors('max_units');
    }

    public function test_only_the_registrar_sets_the_unit_cap(): void
    {
        foreach (['admission', 'chair', 'cashier', 'student'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->post('/registrar/max-units', ['max_units' => 30])->assertForbidden();
        }
    }
}
