<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\DiscountType;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TransactionLedger;
use App\Models\User;
use App\Services\FeeAssessmentService;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeAssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(array $overrides = []): User
    {
        return User::factory()->create(array_merge(
            ['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year'],
            $overrides
        ));
    }

    private function makeBlockSection(int $units = 3): Section
    {
        $program = Program::where('code', 'BSOA')->first();
        $subject = Subject::factory()->for($program)->create(['year_level' => 1, 'semester' => 1, 'units' => $units]);

        return Section::factory()->for($subject)->create(['block_label' => 'A', 'school_year' => '2026-2027']);
    }

    public function test_regular_student_assessed_on_block_load(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent();
        $this->makeBlockSection(3);
        $this->makeBlockSection(2);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertSame(5, $b['units']);
        $this->assertSame(300, $b['rate']);
        $this->assertEqualsWithDelta(1500.0, (float) $b['tuition'], 0.001);
        $this->assertEqualsWithDelta(3000.0, (float) $b['assessment'], 0.001); // 1500 tuition + 1500 misc
        $this->assertEqualsWithDelta(3000.0, (float) $b['balance'], 0.001);
        $this->assertFalse($b['fully_paid']);
    }

    public function test_enrolled_student_assessed_on_enrolled_load(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent();
        $this->makeBlockSection(3); // block says 3 units...
        $enrolledSection = $this->makeBlockSection(4);
        $enrollment = Enrollment::factory()->create([
            'user_id' => $student->id, 'status' => 'enrolled',
            'school_year' => '2026-2027', 'semester' => 1,
        ]);
        $enrollment->sections()->attach($enrolledSection->id);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertSame(4, $b['units']); // ...but the actual enrollment wins
    }

    public function test_irregular_student_assessed_on_eligible_subjects(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent();
        $program = Program::where('code', 'BSOA')->first();
        $failed = Subject::factory()->for($program)->create(['semester' => 1, 'units' => 3, 'code' => 'FAIL1']);
        Subject::factory()->for($program)->create(['semester' => 1, 'units' => 2, 'code' => 'ELIG1']);
        $student->grades()->create(['subject_code' => 'FAIL1', 'status' => 'Failed']);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        // FAIL1 (retake, 3u) + ELIG1 (2u) are both eligible
        $this->assertSame(5, $b['units']);
    }

    public function test_discount_applies_to_tuition_only(): void
    {
        $this->seed(ProgramSeeder::class);
        $type = DiscountType::factory()->create(['name' => 'Academic Scholar', 'percent' => 50]);
        $student = $this->makeStudent(['discount_type_id' => $type->id]);
        $this->makeBlockSection(10); // 3000 tuition

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertSame('Academic Scholar', $b['discount_name']);
        $this->assertSame(50, $b['discount_percent']);
        $this->assertEqualsWithDelta(1500.0, (float) $b['discount_amount'], 0.001);
        // 3000 - 1500 + 1500 misc = 3000; misc untouched by the discount
        $this->assertEqualsWithDelta(3000.0, (float) $b['assessment'], 0.001);
    }

    public function test_settled_payments_reduce_balance_and_full_payment_flags(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent();
        $this->makeBlockSection(5); // assessment 1500 + 1500 = 3000
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Settled', 'amount' => 3000.00,
        ]);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Pending', 'amount' => 999.00, // ignored
        ]);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertEqualsWithDelta(3000.0, (float) $b['paid'], 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $b['balance'], 0.001);
        $this->assertTrue($b['fully_paid']);
    }

    public function test_overpayment_floors_balance_at_zero(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent();
        $this->makeBlockSection(3);
        TransactionLedger::factory()->create([
            'user_id' => $student->id, 'status' => 'Settled', 'amount' => 99999.00,
        ]);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertEqualsWithDelta(0.0, (float) $b['balance'], 0.001);
    }

    public function test_student_without_program_owes_misc_only(): void
    {
        $this->seed(ProgramSeeder::class);
        $student = $this->makeStudent(['major' => null]);

        $b = app(FeeAssessmentService::class)->breakdownFor($student);

        $this->assertSame(0, $b['units']);
        $this->assertEqualsWithDelta(1500.0, (float) $b['assessment'], 0.001);
        $this->assertFalse($b['fully_paid']);
    }
}
