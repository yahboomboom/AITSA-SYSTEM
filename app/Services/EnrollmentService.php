<?php

namespace App\Services;

use App\Exceptions\EnrollmentException;
use App\Models\Clearance;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;

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
        $clearance = Clearance::where('user_id', $user->id)->first();

        return $clearance !== null
            && $clearance->chair_status === 'Approved'
            && $clearance->cashier_status === 'Approved'
            && $clearance->registrar_status === 'Approved';
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
}
