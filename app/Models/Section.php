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
        'room', 'professor', 'capacity', 'school_year', 'faculty_id', 'room_id',
        'delivery_mode', // 'Face-to-Face' or 'Online' — set per section/block.
    ];

    protected $casts = ['days' => 'array'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function roomEntity(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function facultyName(): string
    {
        return $this->faculty?->name ?? $this->professor;
    }

    public function roomLabel(): string
    {
        return $this->roomEntity?->name ?? $this->room;
    }

    public function enrollments(): BelongsToMany
    {
        return $this->belongsToMany(Enrollment::class, 'enrollment_subjects');
    }

    public function enrolledCount(): int
    {
        return $this->enrollments()->where('enrollments.status', '!=', 'rejected')->count();
    }

    public function enrolledStudentIds(): \Illuminate\Support\Collection
    {
        return $this->enrollments()
            ->where('enrollments.status', '!=', 'rejected')
            ->with('user')
            ->get()
            ->pluck('user.id')
            ->filter()
            ->values();
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
    
    // True when this specific block is held online (no physical room needed).
    public function isOnline(): bool
    {
        return $this->delivery_mode === 'Online';
    }

    // True when this specific block meets face-to-face (a physical room applies).
    public function isFaceToFace(): bool
    {
        return $this->delivery_mode !== 'Online';
    }
}
