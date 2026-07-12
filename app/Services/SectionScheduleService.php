<?php

namespace App\Services;

use App\Exceptions\EnrollmentException;
use App\Models\Room;
use App\Models\Section;

class SectionScheduleService
{
    /**
     * Reject the given section attributes if they double-book a professor or a
     * physical room. $attributes must contain days, start_time, end_time,
     * school_year and may contain faculty_id / room_id. Pass the section being
     * edited as $ignore so it does not conflict with itself.
     *
     * @throws EnrollmentException 409 on conflict
     */
    public function assertNoConflicts(array $attributes, ?Section $ignore = null): void
    {
        $facultyId = $attributes['faculty_id'] ?? null;
        $roomId = $attributes['room_id'] ?? null;

        $room = $roomId ? Room::find($roomId) : null;
        $checkRoom = $room !== null && $room->isPhysical();

        if (! $facultyId && ! $checkRoom) {
            return;
        }

        $candidates = Section::query()
            ->where('school_year', $attributes['school_year'])
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore->id))
            ->where(function ($q) use ($facultyId, $roomId, $checkRoom) {
                if ($facultyId) {
                    $q->orWhere('faculty_id', $facultyId);
                }
                if ($checkRoom) {
                    $q->orWhere('room_id', $roomId);
                }
            })
            ->with(['subject', 'faculty'])
            ->get();

        foreach ($candidates as $other) {
            if (! $this->overlaps($attributes, $other)) {
                continue;
            }

            $slot = sprintf('%s %s–%s', implode('/', $other->days), $other->start_time, $other->end_time);

            if ($facultyId && (int) $other->faculty_id === (int) $facultyId) {
                throw new EnrollmentException(sprintf(
                    '%s is already scheduled for %s Block %s (%s).',
                    $other->faculty?->name ?? 'This professor', $other->subject->code, $other->block_label, $slot
                ));
            }

            if ($checkRoom && (int) $other->room_id === (int) $roomId) {
                throw new EnrollmentException(sprintf(
                    'Room %s is already booked for %s Block %s (%s).',
                    $room->name, $other->subject->code, $other->block_label, $slot
                ));
            }
        }
    }

    private function overlaps(array $attributes, Section $other): bool
    {
        if (count(array_intersect($attributes['days'], $other->days)) === 0) {
            return false;
        }

        return $attributes['start_time'] < $other->end_time
            && $other->start_time < $attributes['end_time'];
    }
}
