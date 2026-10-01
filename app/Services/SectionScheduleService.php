<?php

namespace App\Services;

use App\Exceptions\ScheduleConflictException;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Collection;

class SectionScheduleService
{
    /**
     * Reject the given section attributes if, in the same school year and
     * semester, they double-book a professor, a physical room, or the
     * students of the same block (same program, year level and block label).
     * $attributes must contain days, start_time, end_time, school_year and
     * may contain subject_id, block_label, faculty_id, room_id. Pass the
     * section being edited as $ignore so it does not conflict with itself.
     *
     * @throws ScheduleConflictException 409 on conflict
     */
    public function assertNoConflicts(array $attributes, ?Section $ignore = null): void
    {
        $facultyId = $attributes['faculty_id'] ?? null;
        $roomId = $attributes['room_id'] ?? null;
        $room = $roomId ? Room::find($roomId) : null;
        $checkRoom = $room !== null && $room->isPhysical();
        $subject = isset($attributes['subject_id']) ? Subject::find($attributes['subject_id']) : null;
        $blockLabel = $attributes['block_label'] ?? null;
        $checkBlock = $subject !== null && $blockLabel !== null;

        if (! $facultyId && ! $checkRoom && ! $checkBlock) {
            return;
        }

        $candidates = Section::query()
            ->where('school_year', $attributes['school_year'])
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore->id))
            // Sections don't store a semester; their subject does.
            ->when($subject, fn ($q) => $q->whereHas('subject', fn ($s) => $s->where('semester', $subject->semester)))
            ->where(function ($q) use ($facultyId, $roomId, $checkRoom, $checkBlock, $subject, $blockLabel) {
                if ($facultyId) {
                    $q->orWhere('faculty_id', $facultyId);
                }
                if ($checkRoom) {
                    $q->orWhere('room_id', $roomId);
                }
                if ($checkBlock) {
                    $q->orWhere(fn ($b) => $b->where('block_label', $blockLabel)
                        ->whereHas('subject', fn ($s) => $s->where('program_id', $subject->program_id)->where('year_level', $subject->year_level)));
                }
            })
            ->with(['subject', 'faculty'])
            ->get();

        foreach ($candidates as $other) {
            if (! $this->overlaps($attributes, $other)) {
                continue;
            }

            $slot = sprintf('%s %s-%s', implode('/', $other->days), $other->start_time, $other->end_time);
            $where = sprintf('%s Block %s (%s)', $other->subject->code, $other->block_label, $slot);
            $conflict = fn (string $type) => [
                'type' => $type,
                'subject' => $other->subject->code,
                'block' => $other->block_label,
                'slot' => $slot,
                'sectionId' => $other->id,
            ];

            if ($facultyId && (int) $other->faculty_id === (int) $facultyId) {
                throw new ScheduleConflictException(
                    ($other->faculty?->name ?? 'This professor') . " is already teaching {$where} at that time.",
                    $conflict('faculty')
                );
            }

            if ($checkRoom && (int) $other->room_id === (int) $roomId) {
                throw new ScheduleConflictException("Room {$room->name} is already booked for {$where}.", $conflict('room'));
            }

            if ($checkBlock && $other->block_label === $blockLabel
                && (int) $other->subject->program_id === (int) $subject->program_id
                && (int) $other->subject->year_level === (int) $subject->year_level) {
                throw new ScheduleConflictException(
                    "Block {$blockLabel} students already have {$where} at that time.",
                    $conflict('block')
                );
            }
        }
    }

    /**
     * For each section, the ids of the other given sections it overlaps in
     * the same semester (e.g. clashes saved before these checks existed).
     *
     * @param  Collection<int, Section>  $sections  with subject loaded
     * @return array<int, int[]>
     */
    public function clashes(Collection $sections): array
    {
        $list = $sections->values();
        $result = $list->mapWithKeys(fn (Section $s) => [$s->id => []])->all();

        foreach ($list as $i => $a) {
            foreach ($list->slice($i + 1) as $b) {
                if ($a->subject?->semester === $b->subject?->semester
                    && $this->overlaps($a->only(['days', 'start_time', 'end_time']), $b)) {
                    $result[$a->id][] = $b->id;
                    $result[$b->id][] = $a->id;
                }
            }
        }

        return $result;
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
