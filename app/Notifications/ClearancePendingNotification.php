<?php

namespace App\Notifications;

use App\Models\Clearance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the STUDENT the moment a new clearance record is created for them
 * (i.e. right after Clearance::initializeFor() runs) — lets them know a
 * clearance process has started and is now waiting on the different offices.
 */
class ClearancePendingNotification extends Notification
{
    use Queueable;

    public function __construct(public Clearance $clearance)
    {
    }

    /** Deliver by email only, for now. Add 'database' here later for in-app alerts. */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your AITSA Clearance Request Has Been Created')
            ->view('emails.clearance-pending', [
                'student' => $notifiable,
                'clearance' => $this->clearance,
            ]);
    }
}