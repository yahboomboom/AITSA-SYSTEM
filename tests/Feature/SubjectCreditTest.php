<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\SubjectCreditRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectCreditTest extends TestCase
{
    use RefreshDatabase;

    private Program $program;
    private Subject $intro;
    private Subject $advanced;

    protected function setUp(): void
    {
        parent::setUp();
        $this->program = Program::factory()->create(['code' => 'BSOA']);
        $this->intro = Subject::factory()->for($this->program)->create(['code' => 'OA101', 'title' => 'Intro to OA', 'year_level' => 1, 'semester' => 1]);
        $this->advanced = Subject::factory()->for($this->program)->create(['code' => 'OA201', 'title' => 'Advanced OA', 'year_level' => 2, 'semester' => 1]);
    }

    private function student(string $type = 'TRANSFEREE'): User
    {
        return User::factory()->create(['role' => 'student', 'major' => 'BSOA', 'applicant_type' => $type, 'year_level' => '2nd Year']);
    }

    private function submit(User $student, array $subjects, string $note = 'From TOR'): \Illuminate\Testing\TestResponse
    {
        $registrar = User::factory()->create(['role' => 'registrar']);

        return $this->actingAs($registrar)->postJson("/registrar/students/{$student->id}/credits", [
            'subjects' => $subjects,
            'note' => $note,
        ]);
    }

    public function test_registrar_sees_the_curriculum_for_a_transferee(): void
    {
        $student = $this->student();
        StudentGrade::create(['user_id' => $student->id, 'subject_code' => 'OA101', 'status' => 'Passed', 'final_grade' => '1.50']);

        $registrar = User::factory()->create(['role' => 'registrar']);
        $data = $this->actingAs($registrar)->getJson("/registrar/students/{$student->id}/credits")->assertOk()->json();

        $subjects = collect($data['subjects'])->keyBy('code');
        $this->assertSame('passed', $subjects['OA101']['state']);
        $this->assertSame('available', $subjects['OA201']['state']);
    }

    public function test_only_transferees_and_returnees_can_be_credited(): void
    {
        $registrar = User::factory()->create(['role' => 'registrar']);
        $continuing = $this->student('NEW');

        $this->actingAs($registrar)->getJson("/registrar/students/{$continuing->id}/credits")->assertStatus(422);
        $this->submit($continuing, [['code' => 'OA101']])->assertStatus(422);
        $this->assertSame(0, SubjectCreditRequest::count());

        $this->submit($this->student('RETURNEE'), [['code' => 'OA101']])->assertOk();
    }

    public function test_submitted_credits_wait_for_the_chair_and_do_not_count_yet(): void
    {
        $student = $this->student();

        $this->submit($student, [['code' => 'OA101', 'grade' => '1.75']])->assertOk();

        $this->assertSame([], $student->fresh()->passedSubjectCodes());
        $request = SubjectCreditRequest::with('items')->sole();
        $this->assertSame('pending', $request->status);
        $this->assertSame('1.75', $request->items->first()->final_grade);
    }

    public function test_chair_approval_marks_subjects_passed_and_credited(): void
    {
        $student = $this->student();
        $this->submit($student, [['code' => 'OA101', 'grade' => '1.75'], ['code' => 'OA201']])->assertOk();
        $request = SubjectCreditRequest::sole();

        $chair = User::factory()->create(['role' => 'chair']);
        $this->actingAs($chair)->post("/approver/credits/{$request->id}/approve")->assertRedirect();

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertEqualsCanonicalizing(['OA101', 'OA201'], $student->fresh()->passedSubjectCodes());
        $grade = StudentGrade::where('user_id', $student->id)->where('subject_code', 'OA101')->sole();
        $this->assertSame('credited', $grade->source);
        $this->assertSame('1.75', $grade->final_grade);
        $this->assertNull(StudentGrade::where('subject_code', 'OA201')->sole()->final_grade);
    }

    public function test_chair_can_reject_with_a_reason_and_nothing_is_credited(): void
    {
        $student = $this->student();
        $this->submit($student, [['code' => 'OA101']])->assertOk();
        $request = SubjectCreditRequest::sole();

        $chair = User::factory()->create(['role' => 'chair']);
        $this->actingAs($chair)->post("/approver/credits/{$request->id}/reject", ['remarks' => 'Not equivalent'])->assertRedirect();

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('Not equivalent', $request->fresh()->remarks);
        $this->assertSame([], $student->fresh()->passedSubjectCodes());
        // Rejected subjects can be submitted again.
        $this->submit($student, [['code' => 'OA101']])->assertOk();
    }

    public function test_cannot_credit_a_subject_graded_in_system_or_already_pending(): void
    {
        $student = $this->student();
        StudentGrade::create(['user_id' => $student->id, 'subject_code' => 'OA101', 'status' => 'Failed', 'final_grade' => '5.00']);

        $this->submit($student, [['code' => 'OA101']])->assertStatus(422);

        $this->submit($student, [['code' => 'OA201']])->assertOk();
        $this->submit($student, [['code' => 'OA201']])->assertStatus(422);
        $this->assertSame(1, SubjectCreditRequest::count());
    }

    public function test_cannot_credit_a_subject_outside_the_students_program(): void
    {
        $other = Subject::factory()->for(Program::factory()->create(['code' => 'BOM']))->create(['code' => 'BM101']);

        $this->submit($this->student(), [['code' => $other->code]])->assertStatus(422);
    }

    public function test_an_approved_request_cannot_be_approved_or_rejected_again(): void
    {
        $this->submit($this->student(), [['code' => 'OA101']])->assertOk();
        $request = SubjectCreditRequest::sole();
        $chair = User::factory()->create(['role' => 'chair']);

        $this->actingAs($chair)->post("/approver/credits/{$request->id}/approve")->assertRedirect();
        $this->actingAs($chair)->post("/approver/credits/{$request->id}/approve")->assertForbidden();
        $this->actingAs($chair)->post("/approver/credits/{$request->id}/reject", ['remarks' => 'x'])->assertForbidden();
    }

    public function test_only_registrar_submits_and_only_chair_reviews(): void
    {
        $student = $this->student();
        $chair = User::factory()->create(['role' => 'chair']);
        $this->actingAs($chair)->postJson("/registrar/students/{$student->id}/credits", ['subjects' => [['code' => 'OA101']]])->assertForbidden();

        $this->submit($student, [['code' => 'OA101']])->assertOk();
        $request = SubjectCreditRequest::sole();
        $registrar = User::factory()->create(['role' => 'registrar']);
        $this->actingAs($registrar)->post("/approver/credits/{$request->id}/approve")->assertForbidden();
    }

    public function test_chair_dashboard_lists_pending_credit_requests(): void
    {
        $student = $this->student();
        $this->submit($student, [['code' => 'OA101', 'grade' => '1.75']], 'From TOR, LSPU')->assertOk();

        $chair = User::factory()->create(['role' => 'chair']);
        $this->actingAs($chair)->get('/approver/dashboard')->assertOk()
            ->assertSee('creditRequests', false)
            ->assertSee('From TOR, LSPU', false)
            ->assertSee('OA101', false);
    }

    public function test_student_search_offers_credits_only_for_transferees_and_returnees(): void
    {
        $transferee = $this->student('TRANSFEREE');
        $returnee = $this->student('RETURNEE');
        $continuing = $this->student('NEW');

        $rows = collect($this->actingAs(User::factory()->create(['role' => 'registrar']))
            ->withSession(['auth.password_confirmed_at' => time()])
            ->getJson('/registrar/students/search?status=all')->assertOk()->json('rows'))->keyBy('id');

        $this->assertStringEndsWith("/registrar/students/{$transferee->id}/credits", $rows[$transferee->id]['creditsUrl']);
        $this->assertNotNull($rows[$returnee->id]['creditsUrl']);
        $this->assertNull($rows[$continuing->id]['creditsUrl']);
    }

    public function test_a_later_in_system_grade_replaces_the_credited_label(): void
    {
        $student = $this->student();
        $this->submit($student, [['code' => 'OA101']])->assertOk();
        $chair = User::factory()->create(['role' => 'chair']);
        $this->actingAs($chair)->post('/approver/credits/' . SubjectCreditRequest::sole()->id . '/approve');

        StudentGrade::upsert(
            [['user_id' => $student->id, 'subject_code' => 'OA101', 'status' => 'Passed', 'final_grade' => '1.25', 'source' => null]],
            ['user_id', 'subject_code'], ['status', 'final_grade', 'source']
        );

        $this->assertNull(StudentGrade::where('subject_code', 'OA101')->sole()->source);
    }
}
