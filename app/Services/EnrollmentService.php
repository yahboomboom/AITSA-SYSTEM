<?php

namespace App\Services;

use App\Exceptions\EnrollmentException;
use App\Models\AuditLog;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    /** @return array{school_year: string, semester: int} */
    public function currentTerm(): array
    {
        return [
            'school_year' => Setting::get('school_year', '2026-2027'),
            'semester' => (int) Setting::get('semester', '1'),
        ];
    }

    public function clearanceComplete(User $user): bool
    {
        $clearance = Clearance::with('items')->where('user_id', $user->id)->first();

        return $clearance !== null
            && $clearance->chair_status === 'Approved'
            && $clearance->cashier_status === 'Approved'
            && $clearance->registrar_status === 'Approved'
            && $clearance->allItemsApproved();
    }

    public function activeEnrollment(User $user): ?Enrollment
    {
        $term = $this->currentTerm();

        return Enrollment::where('user_id', $user->id)
            ->where('school_year', $term['school_year'])
            ->where('semester', $term['semester'])
            ->first();
    }

    public function assertCanEnroll(User $user): void
    {
        if (! $this->clearanceComplete($user)) {
            throw new EnrollmentException('Your clearance is not yet complete. Settle all departments before enrolling.');
        }

        $existing = $this->activeEnrollment($user);
        if ($existing && $existing->status !== 'rejected') {
            throw new EnrollmentException('You already have an enrollment for this term (status: ' . $existing->status . ').');
        }

        $program = $user->program();
        if (! $program || ! $program->is_enrollable) {
            throw new EnrollmentException('Your program does not support self-service enrollment. Please visit the Registrar.');
        }
    }

    /** @return array{label: string, sections: \Illuminate\Support\Collection}|null */
    public function blockFor(User $user): ?array
    {
        $term = $this->currentTerm();
        $program = $user->program();
        if (! $program) {
            return null;
        }

        $grouped = Section::where('school_year', $term['school_year'])
            ->whereHas('subject', fn ($q) => $q
                ->where('program_id', $program->id)
                ->where('year_level', $user->yearNumber())
                ->where('semester', $term['semester']))
            ->with('subject')
            ->get()
            ->groupBy('block_label')
            ->sortKeys();

        foreach ($grouped as $label => $sections) {
            if ($sections->every(fn (Section $s) => $s->hasSeats())) {
                return ['label' => $label, 'sections' => $sections->values()];
            }
        }

        return null;
    }

    public function enrollRegular(User $user): Enrollment
    {
        $this->assertCanEnroll($user);
        $term = $this->currentTerm();

        return DB::transaction(function () use ($user, $term) {
            $block = $this->blockFor($user);
            if (! $block) {
                throw new EnrollmentException('No block schedule with open seats is available for your program and year level. Please contact the Registrar.');
            }

            $locked = Section::whereIn('id', $block['sections']->pluck('id'))->lockForUpdate()->get();
            foreach ($locked as $section) {
                if (! $section->hasSeats()) {
                    throw new EnrollmentException('A section in your block just filled up. Please try again.');
                }
            }

            $enrollment = $this->upsertEnrollment($user, $term, [
                'type' => 'regular', 'status' => 'enrolled', 'block_label' => $block['label'], 'remarks' => null,
            ]);
            $enrollment->sections()->sync($locked->pluck('id'));

            AuditLog::record(
                'Enrollment Committed',
                sprintf('Regular enrollment committed for %s (%s), block %s, %s sem %d.', $user->name, $user->login_id, $block['label'], $term['school_year'], $term['semester']),
                'Enrollment',
                $enrollment->id
            );

            return $enrollment->load('sections.subject');
        });
    }

    public function catalogueFor(User $user): \Illuminate\Support\Collection
    {
        $term = $this->currentTerm();
        $program = $user->program();
        if (! $program) {
            return collect();
        }

        $passed = $user->passedSubjectCodes();

        return Subject::where('program_id', $program->id)
            ->where('semester', $term['semester'])
            ->where('year_level', '<=', $user->yearNumber())
            ->with(['prerequisites', 'sections' => fn ($q) => $q->where('school_year', $term['school_year'])])
            ->orderBy('year_level')->orderBy('code')
            ->get()
            ->map(function (Subject $subject) use ($passed) {
                $missing = $subject->prerequisites->pluck('code')->diff($passed);
                [$eligible, $reason] = match (true) {
                    in_array($subject->code, $passed, true) => [false, 'Already passed'],
                    $missing->isNotEmpty() => [false, 'Missing prerequisite: ' . $missing->implode(', ')],
                    default => [true, null],
                };

                return [
                    'id' => $subject->id,
                    'code' => $subject->code,
                    'title' => $subject->title,
                    'units' => $subject->units,
                    'year_level' => $subject->year_level,
                    'semester' => $subject->semester,
                    'mode' => $subject->mode,
                    'eligible' => $eligible,
                    'reason' => $reason,
                    'sections' => $subject->sections->map(fn (Section $s) => [
                        'id' => $s->id,
                        'block_label' => $s->block_label,
                        'days' => $s->days,
                        'start_time' => $s->start_time,
                        'end_time' => $s->end_time,
                        'room' => $s->room,
                        'professor' => $s->professor,
                        'seats_left' => $s->seatsLeft(),
                    ])->values()->all(),
                ];
            })
            ->values();
    }

    /** @param int[] $sectionIds */
    public function enrollIrregular(User $user, array $sectionIds): Enrollment
    {
        $this->assertCanEnroll($user);
        $term = $this->currentTerm();

        if (empty($sectionIds)) {
            throw new EnrollmentException('Select at least one subject to enroll.', 422);
        }

        return DB::transaction(function () use ($user, $term, $sectionIds) {
            $sections = Section::whereIn('id', $sectionIds)->lockForUpdate()->with('subject.prerequisites')->get();

            if ($sections->count() !== count(array_unique($sectionIds))) {
                throw new EnrollmentException('One or more selected sections no longer exist.', 422);
            }

            $program = $user->program();
            $passed = $user->passedSubjectCodes();

            if ($sections->pluck('subject_id')->duplicates()->isNotEmpty()) {
                throw new EnrollmentException('You selected more than one section of the same subject.', 422);
            }

            foreach ($sections as $section) {
                $subject = $section->subject;
                if ($subject->program_id !== $program->id || $subject->semester !== $term['semester'] || $section->school_year !== $term['school_year']) {
                    throw new EnrollmentException("Section for {$subject->code} is not offered to your program this term.", 422);
                }
                if (in_array($subject->code, $passed, true)) {
                    throw new EnrollmentException("You have already passed {$subject->code}.");
                }
                $missing = $subject->prerequisites->pluck('code')->diff($passed);
                if ($missing->isNotEmpty()) {
                    throw new EnrollmentException("{$subject->code} requires: " . $missing->implode(', ') . '.');
                }
                if (! $section->hasSeats()) {
                    throw new EnrollmentException("The {$subject->code} section you picked just filled up. Choose another section.");
                }
            }

            foreach ($sections as $i => $a) {
                foreach ($sections->slice($i + 1) as $b) {
                    if ($a->overlaps($b)) {
                        throw new EnrollmentException("Schedule conflict: {$a->subject->code} overlaps with {$b->subject->code}.");
                    }
                }
            }

            $enrollment = $this->upsertEnrollment($user, $term, [
                'type' => 'irregular', 'status' => 'pending', 'block_label' => null, 'remarks' => null,
            ]);
            $enrollment->sections()->sync($sections->pluck('id'));

            AuditLog::record(
                'Enrollment Submitted',
                sprintf('Irregular enrollment submitted for %s (%s) with %d subject(s); awaiting Department Chair approval.', $user->name, $user->login_id, $sections->count()),
                'Enrollment',
                $enrollment->id
            );

            return $enrollment->load('sections.subject');
        });
    }

    /** @param array{school_year: string, semester: int} $term */
    private function upsertEnrollment(User $user, array $term, array $attributes): Enrollment
    {
        return Enrollment::updateOrCreate(
            ['user_id' => $user->id, 'school_year' => $term['school_year'], 'semester' => $term['semester']],
            $attributes
        );
    }
}
