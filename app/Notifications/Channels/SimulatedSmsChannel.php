<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SimulatedSmsChannel
{
    /**
     * Send the given notification via simulated SMS.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $data = $notification->toSms($notifiable);
        $phone = is_array($data) ? ($data['phone'] ?? ($notifiable->phone ?? 'simulated-recipient')) : ($notifiable->phone ?? 'simulated-recipient');
        $message = is_array($data) ? ($data['message'] ?? json_encode($data)) : (string) $data;

        Log::info("Simulated SMS sent to {$phone}: {$message}", [
            'notifiable_id' => $notifiable->id ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
