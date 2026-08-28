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
     * Applicants counted as occupying a slot: those who have reserved (paid or
     * marked reserved by the Registrar) their spot in this curriculum for the
     * current registration period. Declined/removed applicants are naturally
     * excluded since their User row is deleted on decline.
     */
    public function takenCount(): int
    {
        return User::where('program_key', $this->program_key)
            ->where('is_reserved', true)
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