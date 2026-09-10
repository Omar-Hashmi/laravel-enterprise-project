<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookChannel
{
    /**
     * Send the given notification via Webhook or Slack.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebhook')) {
            return;
        }

        $data = $notification->toWebhook($notifiable);
        $url = is_array($data) ? ($data['url'] ?? config('services.webhook.url')) : config('services.webhook.url');
        $payload = is_array($data) ? ($data['payload'] ?? $data) : ['message' => (string) $data];

        if (empty($url)) {
            Log::info('WebhookChannel skipped: No webhook URL configured.', ['payload' => $payload]);

            return;
        }

        try {
            Http::timeout(5)->post($url, $payload);
        } catch (Throwable $e) {
            Log::error("WebhookChannel dispatch failed: {$e->getMessage()}", [
                'url' => $url,
                'payload' => $payload,
            ]);
        }
    }
}
