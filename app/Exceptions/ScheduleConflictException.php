<?php

namespace App\Exceptions;

/**
 * A section would double-book a professor, a room, or a block's students.
 * $conflict = ['type' => faculty|room|block, 'subject', 'block', 'slot', 'sectionId'].
 */
class ScheduleConflictException extends EnrollmentException
{
    public function __construct(string $message, public readonly array $conflict)
    {
        parent::__construct($message, 409);
    }
}
