<?php

namespace Tests\Feature;

use App\Models\GradeSubmission;
use App\Models\GradeSubmissionItem;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeSubmissionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_submission_defaults_to_draft_and_exposes_its_relations(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $subject = Subject::factory()->create(['code' => 'CC101']);
        $section = Section::factory()->create(['subject_id' => $subject->id, 'faculty_id' => $faculty->id]);
        $student = User::factory()->create(['role' => 'student']);

        $submission = GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id]);

        $this->assertSame('draft', $submission->status);
        $this->assertTrue($submission->section->is($section));
        $this->assertTrue($submission->faculty->is($faculty));

        $item = GradeSubmissionItem::create([
            'grade_submission_id' => $submission->id,
            'user_id' => $student->id,
            'final_grade' => '88',
            'status' => 'Passed',
        ]);

        $this->assertTrue($submission->items()->first()->is($item));
        $this->assertTrue($item->user->is($student));
    }

    public function test_only_one_grade_submission_row_per_section(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $section = Section::factory()->create(['faculty_id' => $faculty->id]);
        GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        GradeSubmission::create(['section_id' => $section->id, 'faculty_id' => $faculty->id]);
    }
}
