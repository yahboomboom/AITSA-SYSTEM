<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Every notification in this app is mail-only with no fallback channel, so
 * an unreachable/misconfigured mail server throws straight out of ->notify()
 * and crashes whatever action triggered it — account creation, clearance
 * sign-offs, payment settlement, etc. Notification delivery must never be
 * able to block the underlying action from succeeding.
 */
class SafeNotify
{
    public static function send(mixed $notifiable, object $notification): void
    {
        try {
            $notifiable?->notify($notification);
        } catch (\Throwable $e) {
            Log::error('Notification delivery failed: ' . get_class($notification) . ' — ' . $e->getMessage());
        }
    }
}
