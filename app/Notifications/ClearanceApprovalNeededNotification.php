<?php

namespace App\Notifications;

use App\Models\ClearanceItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to an APPROVER (department head/officer, chair, cashier, or registrar)
 * when a clearance item is now sitting in their queue, waiting to be signed.
 */
class ClearanceApprovalNeededNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $queueLabel  Human-readable name of the dashboard/queue this landed in,
     *                              e.g. "Department Clearance Queue", "Cashier Clearance Queue".
     */
    public function __construct(
        public ClearanceItem $item,
        public string $queueLabel = 'Clearance Queue',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $student = $this->item->clearance->user;

        return (new MailMessage)
            ->subject('New Clearance Item Pending Your Approval')
            ->view('emails.clearance-approval-needed', [
                'approver' => $notifiable,
                'studentName' => $student->name ?? ('Student #' . $this->item->clearance->user_id),
                'queueLabel' => $this->queueLabel,
                'departmentName' => $this->item->department->name ?? null,
            ]);
    }
}