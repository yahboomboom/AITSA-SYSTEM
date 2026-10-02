<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionSlotLimit extends Model
{
    protected $fillable = ['program_key', 'program_name', 'school_year', 'total_slots', 'sections'];

    /**
     * Slots per section, evenly split (e.g. 200 total / 4 sections = 50 each).
     */
    public function slotsPerSection(): int
    {
        if ($this->sections < 1) {
            return $this->total_slots;
        }

        return intdiv($this->total_slots, $this->sections);
    }

    /**
     * Users counted as occupying a slot in this curriculum: every active
     * student (any year level, regular or irregular — including ones imported
     * straight into the database without a reservation), plus applicants who
     * have reserved (paid or marked reserved by the Registrar). Withdrawn
     * students drop out because withdrawal changes their role; declined
     * applicants are deleted.
     */
    public function takenCount(): int
    {
        return User::where('program_key', $this->program_key)
            ->where(fn ($q) => $q->where('role', 'student')
                ->orWhere(fn ($a) => $a->where('role', 'applicant')->where('is_reserved', true)))
            ->count();
    }

    public function slotsLeft(): int
    {
        return max(0, $this->total_slots - $this->takenCount());
    }

    public function isFull(): bool
    {
        return $this->slotsLeft() <= 0;
    }

    /**
     * Get (or lazily create with sensible defaults) the slot-limit row for a
     * curriculum for the given school year, so every program always has one
     * once it's looked up — the Registrar can then edit it.
     */
    public static function forProgram(string $programKey, string $programName, string $schoolYear): self
    {
        return static::firstOrCreate(
            ['program_key' => $programKey, 'school_year' => $schoolYear],
            ['program_name' => $programName, 'total_slots' => 200, 'sections' => 4]
        );
    }
}