<?php

namespace Tests\Feature;

use App\Exceptions\EnrollmentException;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\MatriculationChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private MatriculationChangeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MatriculationChangeService::class);
    }

    /** @return array{0: User, 1: Enrollment, 2: Section, 3: Section} */
    private function enrolledStudent(): array
    {
        Setting::put('change_matriculation_open', '1');

        $program = Program::factory()->create(['code' => 'BSOA', 'is_enrollable' => true]);
        $user = User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'year_level' => '1st Year']);

        $subjectA = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-A']);
        $subjectB = Subject::factory()->create(['program_id' => $program->id, 'code' => 'SUBJ-B']);

        $secA = Section::factory()->create(['subject_id' => $subjectA->id, 'days' => ['M', 'W'], 'start_time' => '08:00', 'end_time' => '09:30']);
        $secB = Section::factory()->create(['subject_id' => $subjectB->id, 'days' => ['T', 'Th'], 'start_time' => '08:00', 'end_time' => '09:30']);

        $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'status' => 'enrolled']);
        $enrollment->sections()->attach([$secA->id, $secB->id]);

        return [$user, $enrollment, $secA, $secB];
    }

    public function test_approve_applies_deltas_and_audits(): void
    {
        [$user, $enrollment, $secA, $secB] = $this->enrolledStudent();
        $swapTarget = Section::factory()->create([
            'subject_id' => $secA->subject_id, 'block_label' => 'B',
            'days' => ['M', 'W'], 'start_time' => '13:00', 'end_time' => '14:30',
        ]);

        $change = $this->service->submit($user, [
            ['action' => 'swap', 'section_id' => $swapTarget->id, 'replaced_section_id' => $secA->id],
            ['action' => 'drop', 'section_id' => $secB->id],
        ]);

        $this->service->approve($change);

        $this->assertSame('approved', $change->fresh()->status);
        $this->assertEqualsCanonicalizing([$swapTarget->id], $enrollment->sections()->pluck('sections.id')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'Matriculation Change Approved']);
    }

    public function test_approve_fails_when_target_seat_disappeared(): void
    {
        [$user, $enrollment, $secA] = $this->enrolledStudent();
        $swapTarget = Section::factory()->create([
            'subject_id' => $secA->subject_id, 'block_label' => 'B', 'capacity' => 1,
            'days' => ['M', 'W'], 'start_time' => '13:00', 'end_time' => '14:30',
        ]);

        $change = $this->service->submit($user, [
            ['action' => 'swap', 'section_id' => $swapTarget->id, 'replaced_section_id' => $secA->id],
        ]);

        // Someone else takes the last seat after submit:
        $other = Enrollment::factory()->create(['status' => 'enrolled']);
        $other->sections()->attach($swapTarget->id);

        try {
            $this->service->approve($change);
            $this->fail('Expected EnrollmentException');
        } catch (EnrollmentException $e) {
            $this->assertStringContainsString('no seats left', $e->getMessage());
        }

        $this->assertSame('pending', $change->fresh()->status);
        $this->assertTrue($enrollment->sections()->pluck('sections.id')->contains($secA->id));
    }

    public function test_reject_stores_remarks(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        $change = $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);

        $this->service->reject($change, 'Load must stay at 2 subjects.');

        $fresh = $change->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Load must stay at 2 subjects.', $fresh->remarks);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Matriculation Change Rejected']);
    }

    public function test_acting_on_non_pending_request_fails(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        $change = $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);
        $this->service->reject($change, 'No.');

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('no longer pending');
        $this->service->approve($change->fresh());
    }

    public function test_student_can_refile_after_rejection(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        $first = $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);
        $this->service->reject($first, 'Try again.');

        $second = $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);

        $this->assertSame('pending', $second->status);
        $this->assertNotSame($first->id, $second->id);
    }
}
