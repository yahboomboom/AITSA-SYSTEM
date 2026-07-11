<?php

namespace App\Exceptions;

class EnrollmentException extends \Exception
{
    public function __construct(string $message, public readonly int $status = 409)
    {
        parent::__construct($message);
    }
}
