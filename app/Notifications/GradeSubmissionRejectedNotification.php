<?php

namespace App\Notifications;

use App\Models\Section;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the FACULTY member whenever a Chair or Registrar rejects a
 * section's grade submission, sending it back to draft for correction.
 */
class GradeSubmissionRejectedNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $office  'Department Chair' or 'Registrar'.
     */
    public function __construct(
        public string $office,
        public Section $section,
        public string $remarks,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->section->loadMissing('subject');

        return (new MailMessage)
            ->subject("Action needed — {$this->office} returned your grade submission")
            ->view('emails.grade-submission-rejected', [
                'faculty' => $notifiable,
                'office' => $this->office,
                'subjectCode' => $this->section->subject->code,
                'blockLabel' => $this->section->block_label,
                'remarks' => $this->remarks,
            ]);
    }
}
