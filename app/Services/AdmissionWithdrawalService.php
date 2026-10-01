<?php

namespace App\Services;

use App\Models\AdmissionSlotLimit;
use App\Models\AuditLog;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Admission-stage withdrawals: a student who paid the (non-refundable)
 * reservation fee, was auto-activated by AdmissionService, and then never
 * continued — usually without telling anyone. The Registrar reviews the
 * flagged list and marks them; nothing here runs automatically.
 */
class AdmissionWithdrawalService
{
    public const REASONS = [
        'no_show' => 'No-show (no response)',
        'withdrew' => 'Withdrew (informed us)',
    ];

    public function thresholdDays(): int
    {
        return max(1, (int) Setting::get('no_show_after_days', '14'));
    }

    /**
     * Current-term clearances of reserved students who have no pending or
     * enrolled enrollment and reserved more than N days ago. The clearance's
     * created_at is when the account was activated, i.e. "reserved on".
     */
    public function possibleNoShows(): Collection
    {
        return $this->noShowClearances()->with('user')->orderBy('created_at')->get();
    }

    /** Shared by the list and canWithdraw(), so the server enforces exactly what the list shows. */
    private function noShowClearances()
    {
        [$schoolYear, $semester] = $this->currentTerm();

        return Clearance::where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->where('created_at', '<=', now()->subDays($this->thresholdDays()))
            ->whereNotIn('user_id', $this->activeEnrollmentUserIds())
            ->whereHas('user', fn ($q) => $this->admissionStage($q->where('role', 'student')->where('is_reserved', true)));
    }

    /**
     * Restrict to students still in their admission term: every clearance
     * they have is for the current term and they have never been enrolled.
     * Without this, continuing students would be flagged after each term
     * rollover (is_reserved never resets and start-new-term gives them a
     * fresh current-term clearance).
     */
    private function admissionStage($query)
    {
        [$schoolYear, $semester] = $this->currentTerm();

        return $query
            ->whereDoesntHave('enrollments', fn ($q) => $q->where('status', 'enrolled'))
            ->whereNotExists(fn ($q) => $q->from('clearances as earlier')
                ->whereColumn('earlier.user_id', 'users.id')
                ->where(fn ($w) => $w->where('earlier.school_year', '!=', $schoolYear)
                    ->orWhere('earlier.semester', '!=', $semester)));
    }

    public function withdrawn(): Collection
    {
        return User::with('withdrawnByUser')
            ->where('role', 'withdrawn')
            ->orderByDesc('withdrawn_at')
            ->get();
    }

    public function canWithdraw(User $student): bool
    {
        return $this->noShowClearances()->where('user_id', $student->id)->exists();
    }

    /** @return bool false if the student isn't eligible — nothing is changed. */
    public function withdraw(User $student, string $reason, ?string $note, User $registrar): bool
    {
        if (! array_key_exists($reason, self::REASONS) || ! $this->canWithdraw($student)) {
            return false;
        }

        [$schoolYear, $semester] = $this->currentTerm();

        DB::transaction(function () use ($student, $reason, $note, $registrar, $schoolYear, $semester) {
            $student->update([
                'role' => 'withdrawn',
                'is_reserved' => false, // frees the slot in AdmissionSlotLimit::takenCount()
                'withdrawn_at' => now(),
                'withdrawal_reason' => $reason,
                'withdrawal_note' => $note,
                'withdrawn_by' => $registrar->id,
            ]);

            Clearance::where('user_id', $student->id)
                ->where('school_year', $schoolYear)
                ->where('semester', $semester)
                ->update(['admission_status' => 'Withdrawn']);

            AuditLog::record(
                'Admission Withdrawn',
                $student->name . ' (' . $student->email . ') marked "' . self::REASONS[$reason] . '" by ' .
                    $registrar->name . ' — reservation fee forfeited.' . ($note ? ' Note: ' . $note : ''),
                'User',
                $student->id
            );
        });

        return true;
    }

    /** @throws \DomainException with a message safe to show the Registrar */
    public function reinstate(User $student, User $registrar): void
    {
        if ($student->role !== 'withdrawn') {
            throw new \DomainException($student->name . ' is not withdrawn.');
        }

        [$schoolYear, $semester] = $this->currentTerm();

        DB::transaction(function () use ($student, $registrar, $schoolYear, $semester) {
            // Checked inside the transaction with the slot row locked, so two
            // reinstates at once can't both take the last slot.
            if ($student->program_key) {
                $programName = collect(config('curricula'))->firstWhere('id', $student->program_key)['name'] ?? $student->program_key;
                $limit = AdmissionSlotLimit::forProgram($student->program_key, $programName, $schoolYear);
                $limit = AdmissionSlotLimit::whereKey($limit->id)->lockForUpdate()->first();
                if ($limit->isFull()) {
                    throw new \DomainException($programName . ' has no free slot left, so ' . $student->name . ' cannot be reinstated.');
                }
            }

            $student->update([
                'role' => 'student',
                'is_reserved' => true, // the forfeited fee counts as their reservation again
                'withdrawn_at' => null,
                'withdrawal_reason' => null,
                'withdrawal_note' => null,
                'withdrawn_by' => null,
            ]);

            // A student withdrawn in an earlier term has no current-term
            // clearance yet (start-new-term skips withdrawn users).
            Clearance::initializeFor($student->id, $schoolYear, $semester, [
                'admission_status' => 'Approved',
                'chair_status' => 'Pending',
                'cashier_status' => 'Pending',
                'registrar_status' => 'Pending',
            ])->update(['admission_status' => 'Approved']);

            AuditLog::record(
                'Admission Reinstated',
                $student->name . ' (' . $student->email . ') reinstated by ' . $registrar->name . '.',
                'User',
                $student->id
            );
        });
    }

    private function currentTerm(): array
    {
        return [Setting::get('school_year', '2026-2027'), (int) Setting::get('semester', '1')];
    }

    private function activeEnrollmentUserIds(): Collection
    {
        [$schoolYear, $semester] = $this->currentTerm();

        return Enrollment::where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->whereIn('status', ['pending', 'enrolled'])
            ->pluck('user_id');
    }
}
