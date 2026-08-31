<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the STUDENT whenever one of their clearance items (or the overall
 * clearance) is Approved or put on Hold, so they don't have to keep
 * refreshing the portal to find out.
 */
class ClearanceStatusUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $office    Which office changed the status, e.g. "Department Chair", "Cashier", "Registrar".
     * @param  string  $status    New status: 'Approved' or 'Hold'.
     * @param  string|null  $remarks  Required when $status is 'Hold'.
     */
    public function __construct(
        public string $office,
        public string $status,
        public ?string $remarks = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->status === 'Approved'
            ? "Good news — {$this->office} cleared you"
            : "Action needed — {$this->office} put your clearance on hold";

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.clearance-status-updated', [
                'student' => $notifiable,
                'office' => $this->office,
                'status' => $this->status,
                'remarks' => $this->remarks,
            ]);
    }
}