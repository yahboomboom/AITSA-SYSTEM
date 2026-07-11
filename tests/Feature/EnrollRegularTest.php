<?php

namespace Tests\Feature;

use App\Exceptions\EnrollmentException;
use App\Models\AuditLog;
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

class EnrollRegularTest extends TestCase
{
    use RefreshDatabase;

    private function makeClearedStudent(): User
    {
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);
        Clearance::create([
            'user_id' => $user->id,
            'chair_status' => 'Approved', 'cashier_status' => 'Approved', 'registrar_status' => 'Approved',
            'library_status' => 'Approved', 'clinic_status' => 'Approved',
        ]);

        return $user;
    }

    private function makeBlockSection(array $overrides = []): Section
    {
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1]);

        return Section::factory()->for($subject)->create(array_merge(['block_label' => 'A', 'school_year' => '2026-2027'], $overrides));
    }

    public function test_regular_enrollment_commits_block_and_audits(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        $section = $this->makeBlockSection();

        $enrollment = app(EnrollmentService::class)->enrollRegular($user);

        $this->assertSame('enrolled', $enrollment->status);
        $this->assertSame('regular', $enrollment->type);
        $this->assertSame('A', $enrollment->block_label);
        $this->assertTrue($enrollment->sections->first()->is($section));
        $this->assertDatabaseHas('audit_logs', ['action' => 'Enrollment Committed', 'target_id' => $enrollment->id]);
    }

    public function test_no_available_block_is_a_clean_conflict(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        $section = $this->makeBlockSection(['capacity' => 1]);
        Enrollment::factory()->create(['status' => 'enrolled'])->sections()->attach($section->id);

        try {
            app(EnrollmentService::class)->enrollRegular($user);
            $this->fail('Expected EnrollmentException');
        } catch (EnrollmentException $e) {
            $this->assertSame(409, $e->status);
            $this->assertDatabaseMissing('enrollments', ['user_id' => $user->id]);
        }
    }

    public function test_rejected_row_is_reused_on_resubmit(): void
    {
        $this->seed(ProgramSeeder::class);
        $user = $this->makeClearedStudent();
        $this->makeBlockSection();
        $rejected = Enrollment::factory()->create([
            'user_id' => $user->id, 'type' => 'irregular', 'status' => 'rejected', 'remarks' => 'Overloaded',
        ]);

        $enrollment = app(EnrollmentService::class)->enrollRegular($user);

        $this->assertSame($rejected->id, $enrollment->id);
        $this->assertSame('enrolled', $enrollment->status);
        $this->assertNull($enrollment->remarks);
        $this->assertSame(1, Enrollment::where('user_id', $user->id)->count());
    }
}
