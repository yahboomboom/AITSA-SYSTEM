<?php

namespace App\Services;

use App\Exceptions\EnrollmentException;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\MatriculationChange;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatriculationChangeService
{
    public function __construct(private readonly EnrollmentService $enrollments)
    {
    }

    public function windowOpen(): bool
    {
        return Setting::get('change_matriculation_open', '0') === '1';
    }

    public function latestRequestFor(Enrollment $enrollment): ?MatriculationChange
    {
        return MatriculationChange::where('enrollment_id', $enrollment->id)->latest('id')->first();
    }

    /** @param array<int, array{action: string, section_id: int, replaced_section_id?: int|null}> $items */
    public function submit(User $user, array $items): MatriculationChange
    {
        if (! $this->windowOpen()) {
            throw new EnrollmentException('The change of matriculation window is closed.');
        }

        $enrollment = $this->enrollments->activeEnrollment($user);
        if (! $enrollment || $enrollment->status !== 'enrolled') {
            throw new EnrollmentException('You must be officially enrolled before requesting a change of matriculation.');
        }

        if (MatriculationChange::where('enrollment_id', $enrollment->id)->where('status', 'pending')->exists()) {
            throw new EnrollmentException('You already have a pending change request. Wait for the Department Chair to act on it.');
        }

        return DB::transaction(function () use ($user, $enrollment, $items) {
            $this->validate($user, $enrollment, $items);

            $change = MatriculationChange::create([
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
            foreach ($items as $item) {
                $change->items()->create([
                    'action' => $item['action'],
                    'section_id' => $item['section_id'],
                    'replaced_section_id' => $item['replaced_section_id'] ?? null,
                ]);
            }

            AuditLog::record(
                'Matriculation Change Submitted',
                sprintf('%s (%s) requested %d change(s) to their enrollment; awaiting Department Chair approval.', $user->name, $user->login_id, count($items)),
                'MatriculationChange',
                $change->id
            );

            return $change->load('items.section.subject', 'items.replacedSection.subject');
        });
    }

    public function approve(MatriculationChange $change): void
    {
        if ($change->status !== 'pending') {
            throw new EnrollmentException('This change request is no longer pending.');
        }

        DB::transaction(function () use ($change) {
            $items = $change->items()->get()->map(fn ($i) => [
                'action' => $i->action,
                'section_id' => $i->section_id,
                'replaced_section_id' => $i->replaced_section_id,
            ])->all();

            // Hold the target section rows so seat counts cannot drift mid-approval.
            $targetIds = collect($items)->whereIn('action', ['add', 'swap'])->pluck('section_id');
            Section::whereIn('id', $targetIds)->lockForUpdate()->get();

            $enrollment = $change->enrollment;
            [$attach, $detach] = $this->validate($change->user, $enrollment, $items);

            if ($detach->isNotEmpty()) {
                $enrollment->sections()->detach($detach->all());
            }
            if ($attach->isNotEmpty()) {
                $enrollment->sections()->attach($attach->all());
            }

            $change->update(['status' => 'approved']);
        });

        AuditLog::record(
            'Matriculation Change Approved',
            'Department Chair approved change of matriculation for ' . ($change->user->name ?? 'ID ' . $change->user_id) . ' (' . ($change->user->login_id ?? 'N/A') . ').',
            'MatriculationChange',
            $change->id
        );
    }

    public function reject(MatriculationChange $change, string $remarks): void
    {
        if ($change->status !== 'pending') {
            throw new EnrollmentException('This change request is no longer pending.');
        }

        $change->update(['status' => 'rejected', 'remarks' => $remarks]);

        AuditLog::record(
            'Matriculation Change Rejected',
            'Department Chair rejected change of matriculation for ' . ($change->user->name ?? 'ID ' . $change->user_id) . ': ' . $remarks,
            'MatriculationChange',
            $change->id
        );
    }

    /**
     * Validate items against the enrollment's resulting schedule.
     * Returns [$attach, $detach] section-id collections for approval to apply.
     *
     * @param array<int, array{action: string, section_id: int, replaced_section_id?: int|null}> $items
     * @return array{0: Collection<int, int>, 1: Collection<int, int>}
     */
    private function validate(User $user, Enrollment $enrollment, array $items): array
    {
        if (count($items) < 1 || count($items) > 10) {
            throw new EnrollmentException('A change request must contain between 1 and 10 items.', 422);
        }

        $term = $this->enrollments->currentTerm();
        $program = $user->program();
        $passed = $user->passedSubjectCodes();

        $targets = Section::whereIn('id', collect($items)->pluck('section_id'))
            ->with('subject.prerequisites')->get()->keyBy('id');

        $detach = collect();
        $attach = collect();
        $resulting = $enrollment->sections()->with('subject')->get()->keyBy('id');

        foreach ($items as $item) {
            $target = $targets->get($item['section_id']);
            if (! $target) {
                throw new EnrollmentException('One of the selected sections no longer exists.', 422);
            }

            if ($item['action'] === 'drop') {
                if (! $resulting->has($target->id)) {
                    throw new EnrollmentException("You are not enrolled in the {$target->subject->code} section you are trying to drop.", 422);
                }
                $resulting->forget($target->id);
                $detach->push($target->id);
                continue;
            }

            if ($item['action'] === 'swap') {
                $replaced = $resulting->get($item['replaced_section_id'] ?? 0);
                if (! $replaced) {
                    throw new EnrollmentException('The section you are trying to swap out is not part of your enrollment.', 422);
                }
                if ($replaced->subject_id !== $target->subject_id) {
                    throw new EnrollmentException('You can only swap to another section of the same subject.', 422);
                }
                if ($replaced->id === $target->id) {
                    throw new EnrollmentException('You are already enrolled in that section.', 422);
                }
                $resulting->forget($replaced->id);
                $detach->push($replaced->id);
            }

            // From here $item['action'] is add or swap: $target joins the schedule.
            $subject = $target->subject;
            if ($target->school_year !== $term['school_year'] || $subject->semester !== $term['semester'] || $subject->program_id !== $program?->id) {
                throw new EnrollmentException("Section for {$subject->code} is not offered to your program this term.", 422);
            }
            if ($resulting->contains(fn (Section $s) => $s->subject_id === $subject->id)) {
                throw new EnrollmentException("You already have {$subject->code} in your schedule.");
            }
            if ($item['action'] === 'add') {
                if (in_array($subject->code, $passed, true)) {
                    throw new EnrollmentException("You have already passed {$subject->code}.");
                }
                $missing = $subject->prerequisites->pluck('code')->diff($passed);
                if ($missing->isNotEmpty()) {
                    throw new EnrollmentException("{$subject->code} requires: " . $missing->implode(', ') . '.');
                }
            }
            if (! $target->hasSeats()) {
                throw new EnrollmentException("The {$subject->code} section you picked has no seats left.");
            }
            foreach ($resulting as $existing) {
                if ($target->overlaps($existing)) {
                    throw new EnrollmentException("Schedule conflict: {$subject->code} overlaps with {$existing->subject->code}.");
                }
            }

            $resulting->put($target->id, $target);
            $attach->push($target->id);
        }

        if ($detach->duplicates()->isNotEmpty() || $attach->duplicates()->isNotEmpty()) {
            throw new EnrollmentException('Your request references the same section more than once.', 422);
        }

        if ($resulting->isEmpty()) {
            throw new EnrollmentException('You cannot drop your entire subject load. Keep at least one subject.');
        }

        return [$attach, $detach];
    }
}
