<?php

namespace App\Services;

use App\Mail\ApplicantAccountCreated;
use App\Models\AuditLog;
use App\Models\Clearance;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Converts a reserved applicant straight into a live student account —
 * no Registrar verify/decline step. Triggered the moment an applicant's
 * reservation fee is confirmed paid (online via PayMongo, or marked paid
 * at the counter by the Registrar), matching the same event that already
 * makes AdmissionSlotLimit::takenCount() count them as occupying a slot.
 */
class AdmissionService
{
    /**
     * @return array{login_id: string, password: string}|null  null if the
     *   user isn't an applicant awaiting activation (already converted, or
     *   never was an applicant) — safe to call more than once.
     */
    public function activateStudentAccount(User $applicant): ?array
    {
        if ($applicant->role !== 'applicant') {
            return null;
        }

        $loginId = $this->generateUniqueLoginId();
        $password = Str::password(10, letters: true, numbers: true, symbols: false, spaces: false);

        $applicant->update([
            'login_id' => $loginId,
            'password' => $password, // cast to 'hashed' on the model, hashed automatically on save
            'role' => 'student',
            'year_level' => $applicant->year_level ?? '1st Year',
        ]);

        $isFirstEverClearance = Clearance::where('user_id', $applicant->id)->doesntExist();

        Clearance::initializeFor(
            $applicant->id,
            \App\Models\Setting::get('school_year', '2026-2027'),
            (int) \App\Models\Setting::get('semester', '1'),
            [
                'admission_status' => 'Approved',
                'chair_status' => 'Pending',
                'cashier_status' => 'Pending',
                'registrar_status' => 'Pending',
                'is_provisional' => $isFirstEverClearance,
            ]
        );

        AuditLog::record(
            'Student Account Auto-Created',
            'Reservation fee confirmed for ' . $applicant->name . ' (' . $applicant->email . ') — student account ' .
                $loginId . ' created automatically, no Registrar review required.',
            'User',
            $applicant->id
        );

        // The account must exist regardless of whether the notification email
        // can be delivered — a broken/unconfigured mail server (very common
        // on local dev setups) would otherwise throw here and abort the
        // request after the DB writes above already committed, leaving the
        // applicant flipped to "student" with no visible confirmation.
        try {
            Mail::to($applicant->email)->send(new ApplicantAccountCreated($applicant, $loginId, $password));
        } catch (\Throwable $e) {
            Log::error('Failed to email new student credentials to ' . $applicant->email . ': ' . $e->getMessage());
        }

        return ['login_id' => $loginId, 'password' => $password];
    }

    private function generateUniqueLoginId(): string
    {
        $year = now()->format('Y');

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = $year . '-' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            if (! User::where('login_id', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Could not generate a unique student ID — please try again.');
    }
}
