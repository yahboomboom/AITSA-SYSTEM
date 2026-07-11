<?php

namespace Tests\Feature;

use App\Exceptions\EnrollmentException;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Services\EnrollmentService;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentBlockTest extends TestCase
{
    use RefreshDatabase;

    private function makeClearedStudent(string $program = 'BSOA', string $year = '1st Year'): User
    {
        $user = User::factory()->create(['role' => 'student', 'major' => $program, 'year_level' => $year]);
        Clearance::create([
            'user_id' => $user->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
        ]);

        return $user;
    }

    private function makeSection(string $programCode, string $block, array $overrides = []): Section
    {
        $program = Program::firstOrCreate(['code' => $programCode], ['name' => $programCode, 'level' => 'bachelor', 'years' => 4]);
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1]);

        return Section::factory()->for($subject)->create(array_merge(['block_label' => $block, 'school_year' => '2026-2027'], $overrides));
    }

    public function test_block_picks_first_label_with_open_seats_everywhere(): void
    {
        $this->seed(ProgramSeeder::class); // sets school_year/semester settings
        $user = $this->makeClearedStudent();

        $fullA = $this->makeSection('BSOA', 'A', ['capacity' => 1]);
        $openB = $this->makeSection('BSOA', 'B');
        // Fill block A's only seat.
        Enrollment::factory()->create(['status' => 'enrolled'])->sections()->attach($fullA->id);

        $block = app(EnrollmentService::class)->blockFor($user);

        $this->assertSame('B', $block['label']);
        $this->assertTrue($block['sections']->first()->is($openB));
    }

    public function test_assert_can_enroll_requires_complete_clearance(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        Clearance::create(['user_id' => $user->id, 'chair_status' => 'Pending', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved']);

        $this->expectException(EnrollmentException::class);
        app(EnrollmentService::class)->assertCanEnroll($user);
    }

    public function test_assert_can_enroll_rejects_duplicate_active_enrollment(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        Enrollment::factory()->create(['user_id' => $user->id, 'status' => 'enrolled']);

        $this->expectException(EnrollmentException::class);
        app(EnrollmentService::class)->assertCanEnroll($user);
    }

    public function test_assert_can_enroll_allows_resubmit_after_rejection(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        Enrollment::factory()->create(['user_id' => $user->id, 'status' => 'rejected']);

        app(EnrollmentService::class)->assertCanEnroll($user);
        $this->assertTrue(true); // no exception thrown
    }
}
