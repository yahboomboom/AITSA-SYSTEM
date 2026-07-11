<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id', 'block_label', 'days', 'start_time', 'end_time',
        'room', 'professor', 'capacity', 'school_year',
    ];

    protected $casts = ['days' => 'array'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function enrollments(): BelongsToMany
    {
        return $this->belongsToMany(Enrollment::class, 'enrollment_subjects');
    }

    public function enrolledCount(): int
    {
        return $this->enrollments()->where('enrollments.status', '!=', 'rejected')->count();
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - $this->enrolledCount());
    }

    public function hasSeats(): bool
    {
        return $this->seatsLeft() > 0;
    }

    public function overlaps(Section $other): bool
    {
        if (count(array_intersect($this->days, $other->days)) === 0) {
            return false;
        }

        return $this->start_time < $other->end_time && $other->start_time < $this->end_time;
    }
}
