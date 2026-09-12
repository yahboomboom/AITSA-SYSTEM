<?php

namespace App\Notifications;

use App\Models\DocumentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DocumentSubmission $submission,
        public string $remarks,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action needed: Your submitted document was rejected')
            ->view('emails.document-rejected', [
                'student' => $notifiable,
                'submission' => $this->submission,
                'remarks' => $this->remarks,
            ]);
    }
}
