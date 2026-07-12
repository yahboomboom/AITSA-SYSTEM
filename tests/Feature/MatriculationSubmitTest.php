<?php

namespace Tests\Feature;

use App\Exceptions\EnrollmentException;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\User;
use App\Services\MatriculationChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculationSubmitTest extends TestCase
{
    use RefreshDatabase;

    private MatriculationChangeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MatriculationChangeService::class);
    }

    /**
     * Enrolled student in program BSOA with two non-conflicting sections:
     * SUBJ-A (M/W 08:00–09:30) and SUBJ-B (T/Th 08:00–09:30).
     *
     * @return array{0: User, 1: Enrollment, 2: Section, 3: Section}
     */
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

    /** New addable subject in the same program with one section. */
    private function addableSection(array $days = ['F'], string $start = '10:00', string $end = '11:30', int $capacity = 40): Section
    {
        $program = Program::where('code', 'BSOA')->firstOrFail();
        $subject = Subject::factory()->create(['program_id' => $program->id]);

        return Section::factory()->create([
            'subject_id' => $subject->id, 'days' => $days,
            'start_time' => $start, 'end_time' => $end, 'capacity' => $capacity,
        ]);
    }

    public function test_window_closed_blocks_submit(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        Setting::put('change_matriculation_open', '0');

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('window is closed');
        $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);
    }

    public function test_must_be_enrolled(): void
    {
        [$user, $enrollment, $secA] = $this->enrolledStudent();
        $enrollment->update(['status' => 'pending']);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('officially enrolled');
        $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);
    }

    public function test_only_one_pending_request(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('pending change request');
        $this->service->submit($user, [['action' => 'drop', 'section_id' => $secA->id]]);
    }

    public function test_add_conflicting_section_rejected(): void
    {
        [$user] = $this->enrolledStudent();
        $clash = $this->addableSection(['M', 'W'], '09:00', '10:00'); // overlaps SUBJ-A

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('Schedule conflict');
        $this->service->submit($user, [['action' => 'add', 'section_id' => $clash->id]]);
    }

    public function test_add_full_section_rejected(): void
    {
        [$user] = $this->enrolledStudent();
        $full = $this->addableSection(['F'], '10:00', '11:30', 1);
        $other = Enrollment::factory()->create(['status' => 'enrolled']);
        $other->sections()->attach($full->id);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('no seats left');
        $this->service->submit($user, [['action' => 'add', 'section_id' => $full->id]]);
    }

    public function test_add_missing_prerequisite_rejected(): void
    {
        [$user] = $this->enrolledStudent();
        $section = $this->addableSection();
        $prereq = Subject::factory()->create(['program_id' => $section->subject->program_id, 'code' => 'PRE-1']);
        $section->subject->prerequisites()->attach($prereq->id);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('requires: PRE-1');
        $this->service->submit($user, [['action' => 'add', 'section_id' => $section->id]]);
    }

    public function test_add_already_passed_subject_rejected(): void
    {
        [$user] = $this->enrolledStudent();
        $section = $this->addableSection();
        StudentGrade::create(['user_id' => $user->id, 'subject_code' => $section->subject->code, 'status' => 'Passed', 'final_grade' => '1.50']);

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('already passed');
        $this->service->submit($user, [['action' => 'add', 'section_id' => $section->id]]);
    }

    public function test_drop_below_one_subject_rejected(): void
    {
        [$user, , $secA, $secB] = $this->enrolledStudent();

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('at least one subject');
        $this->service->submit($user, [
            ['action' => 'drop', 'section_id' => $secA->id],
            ['action' => 'drop', 'section_id' => $secB->id],
        ]);
    }

    public function test_drop_of_section_not_enrolled_rejected(): void
    {
        [$user] = $this->enrolledStudent();
        $foreign = $this->addableSection();

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('not enrolled');
        $this->service->submit($user, [['action' => 'drop', 'section_id' => $foreign->id]]);
    }

    public function test_swap_to_different_subject_rejected(): void
    {
        [$user, , $secA] = $this->enrolledStudent();
        $otherSubject = $this->addableSection();

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('same subject');
        $this->service->submit($user, [
            ['action' => 'swap', 'section_id' => $otherSubject->id, 'replaced_section_id' => $secA->id],
        ]);
    }

    public function test_items_checked_against_combined_result(): void
    {
        [$user] = $this->enrolledStudent();
        $addOne = $this->addableSection(['F'], '10:00', '11:30');
        $addTwo = $this->addableSection(['F'], '11:00', '12:30'); // conflicts with $addOne, not with current load

        $this->expectException(EnrollmentException::class);
        $this->expectExceptionMessage('Schedule conflict');
        $this->service->submit($user, [
            ['action' => 'add', 'section_id' => $addOne->id],
            ['action' => 'add', 'section_id' => $addTwo->id],
        ]);
    }

    public function test_valid_combined_request_goes_pending(): void
    {
        [$user, $enrollment, $secA, $secB] = $this->enrolledStudent();
        $swapTarget = Section::factory()->create([
            'subject_id' => $secA->subject_id, 'block_label' => 'B',
            'days' => ['M', 'W'], 'start_time' => '13:00', 'end_time' => '14:30',
        ]);
        $addSection = $this->addableSection(['F'], '10:00', '11:30');

        $change = $this->service->submit($user, [
            ['action' => 'swap', 'section_id' => $swapTarget->id, 'replaced_section_id' => $secA->id],
            ['action' => 'drop', 'section_id' => $secB->id],
            ['action' => 'add', 'section_id' => $addSection->id],
        ]);

        $this->assertSame('pending', $change->status);
        $this->assertCount(3, $change->items);
        // Enrollment untouched until approval:
        $this->assertEqualsCanonicalizing([$secA->id, $secB->id], $enrollment->sections()->pluck('sections.id')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'Matriculation Change Submitted']);
    }
}
