<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\SubjectCreditRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Crediting subjects a transferee or returnee already passed (from their TOR
 * or old records). The Registrar submits, the Chair approves; only an
 * approved request writes Passed grades, marked source = 'credited'.
 */
class SubjectCreditService
{
    public const ELIGIBLE_TYPES = ['TRANSFEREE', 'RETURNEE'];

    public function isEligible(User $student): bool
    {
        return $student->role === 'student'
            && in_array($student->applicant_type, self::ELIGIBLE_TYPES, true)
            && $student->program() !== null;
    }

    /**
     * The student's curriculum with each subject's state:
     * passed (in-system), credited, failed, pending (awaiting Chair) or available.
     */
    public function overview(User $student): array
    {
        $this->assertEligible($student);
        $program = $student->program();

        $grades = $student->grades()->get()->keyBy('subject_code');
        $pending = $this->pendingCodes($student);

        $subjects = Subject::where('program_id', $program->id)
            ->orderBy('year_level')->orderBy('semester')->orderBy('code')
            ->get()
            ->map(function (Subject $subject) use ($grades, $pending) {
                $grade = $grades->get($subject->code);
                $state = match (true) {
                    $grade && $grade->source === 'credited' => 'credited',
                    $grade && $grade->status === 'Passed' => 'passed',
                    $grade !== null => 'graded',
                    in_array($subject->code, $pending, true) => 'pending',
                    default => 'available',
                };

                return [
                    'code' => $subject->code,
                    'title' => $subject->title,
                    'units' => $subject->units,
                    'yearLevel' => $subject->year_level,
                    'semester' => $subject->semester,
                    'state' => $state,
                    'grade' => $grade?->final_grade,
                ];
            })->values();

        $lastRejected = SubjectCreditRequest::with('items')
            ->where('user_id', $student->id)->where('status', 'rejected')
            ->latest('reviewed_at')->first();

        return [
            'student' => ['name' => $student->name, 'program' => $program->code, 'type' => $student->applicant_type],
            'subjects' => $subjects,
            'placement' => [
                'currentYear' => $student->year_level,
                'suggestedYear' => $this->suggestedYear($student, $program, $subjects),
                'backSubjects' => count($student->backSubjectCodes()),
            ],
            'lastRejected' => $lastRejected ? [
                'remarks' => $lastRejected->remarks,
                'codes' => $lastRejected->items->pluck('subject_code')->values(),
                'at' => $lastRejected->reviewed_at?->format('M d, Y'),
            ] : null,
        ];
    }

    /** @param array<int, array{code:string, grade?:?string}> $subjects */
    public function submit(User $student, User $registrar, array $subjects, ?string $note): SubjectCreditRequest
    {
        $this->assertEligible($student);

        return DB::transaction(function () use ($student, $registrar, $subjects, $note) {
            // Serialize submissions for this student so two tabs can't queue the same subject twice.
            User::whereKey($student->id)->lockForUpdate()->first();

            $codes = collect($subjects)->pluck('code')->unique()->values();
            $curriculum = Subject::where('program_id', $student->program()->id)->whereIn('code', $codes)->get()->keyBy('code');

            $outside = $codes->diff($curriculum->keys());
            if ($outside->isNotEmpty()) {
                throw ValidationException::withMessages(['subjects' => 'Not in ' . $student->major . ': ' . $outside->implode(', ') . '.']);
            }
            $graded = $student->grades()->whereIn('subject_code', $codes)->pluck('subject_code');
            if ($graded->isNotEmpty()) {
                throw ValidationException::withMessages(['subjects' => 'Already has a grade: ' . $graded->implode(', ') . '.']);
            }
            $pending = $codes->intersect($this->pendingCodes($student));
            if ($pending->isNotEmpty()) {
                throw ValidationException::withMessages(['subjects' => 'Already waiting for the Chair: ' . $pending->implode(', ') . '.']);
            }

            $request = SubjectCreditRequest::create([
                'user_id' => $student->id,
                'requested_by' => $registrar->id,
                'note' => $note,
                'status' => 'pending',
            ]);
            $grades = collect($subjects)->keyBy('code');
            foreach ($codes as $code) {
                $request->items()->create([
                    'subject_code' => $code,
                    'subject_title' => $curriculum[$code]->title,
                    'final_grade' => ($grades[$code]['grade'] ?? null) ?: null,
                ]);
            }

            AuditLog::record(
                'Subject Credits Submitted',
                sprintf('Registrar submitted %d subject credit(s) for %s (%s): %s.', $codes->count(), $student->name, $student->login_id, $codes->implode(', ')),
                'SubjectCreditRequest',
                $request->id
            );

            return $request;
        });
    }

    public function approve(SubjectCreditRequest $request, User $chair): void
    {
        DB::transaction(function () use ($request, $chair) {
            $this->claim($request, $chair, 'approved');
            $request->load('items', 'user');

            // A grade given in-system after submission wins over the credit.
            $alreadyGraded = StudentGrade::where('user_id', $request->user_id)
                ->whereIn('subject_code', $request->items->pluck('subject_code'))
                ->pluck('subject_code');

            foreach ($request->items->whereNotIn('subject_code', $alreadyGraded) as $item) {
                StudentGrade::create([
                    'user_id' => $request->user_id,
                    'subject_code' => $item->subject_code,
                    'status' => 'Passed',
                    'final_grade' => $item->final_grade,
                    'source' => 'credited',
                ]);
            }

            AuditLog::record(
                'Subject Credits Approved',
                sprintf('Chair approved subject credits for %s: %s.', $request->user->name ?? 'student', $request->items->pluck('subject_code')->implode(', ')),
                'SubjectCreditRequest',
                $request->id
            );
        });
    }

    public function reject(SubjectCreditRequest $request, User $chair, string $remarks): void
    {
        $this->claim($request, $chair, 'rejected', $remarks);
        $request->load('items', 'user');

        AuditLog::record(
            'Subject Credits Rejected',
            sprintf('Chair rejected subject credits for %s (%s): %s', $request->user->name ?? 'student', $request->items->pluck('subject_code')->implode(', '), $remarks),
            'SubjectCreditRequest',
            $request->id
        );
    }

    /** Atomically moves a pending request to its final status; 403 if it was already reviewed. */
    private function claim(SubjectCreditRequest $request, User $chair, string $status, ?string $remarks = null): void
    {
        $updated = SubjectCreditRequest::whereKey($request->id)->where('status', 'pending')->update([
            'status' => $status,
            'reviewed_by' => $chair->id,
            'reviewed_at' => now(),
            'remarks' => $remarks,
        ]);
        abort_unless($updated === 1, 403, 'This credit request is no longer waiting for the Chair.');
    }

    /**
     * The year after the last fully completed year, counting from 1st year
     * and never skipping past a gap. Pending credits don't count yet.
     */
    private function suggestedYear(User $student, \App\Models\Program $program, \Illuminate\Support\Collection $subjects): string
    {
        $labels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        $maxYear = max(1, min(4, (int) ($program->years ?: 4)));
        $done = fn ($s) => in_array($s['state'], ['passed', 'credited'], true);

        $year = 1;
        while ($year < $maxYear) {
            $inYear = $subjects->where('yearLevel', $year);
            if ($inYear->isEmpty() || ! $inYear->every($done)) {
                break;
            }
            $year++;
        }

        return $labels[$year - 1];
    }

    /** @return string[] */
    private function pendingCodes(User $student): array
    {
        return SubjectCreditRequest::where('user_id', $student->id)->where('status', 'pending')
            ->with('items')->get()->flatMap->items->pluck('subject_code')->all();
    }

    private function assertEligible(User $student): void
    {
        if (! $this->isEligible($student)) {
            throw ValidationException::withMessages(['student' => 'Subject crediting is only for transferee and returnee students with a program.']);
        }
    }
}
