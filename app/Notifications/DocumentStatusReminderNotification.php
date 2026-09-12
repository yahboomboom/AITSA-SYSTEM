<?php

namespace App\Notifications;

use App\Models\DocumentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentStatusReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public DocumentSubmission $submission)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder: Action needed for your submitted document')
            ->view('emails.document-status-reminder', [
                'student' => $notifiable,
                'submission' => $this->submission,
            ]);
    }
}
