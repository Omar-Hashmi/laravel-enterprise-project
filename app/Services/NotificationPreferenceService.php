<?php

namespace App\Services;

use App\Models\User;

class NotificationPreferenceService
{
    /**
     * Determine if a user should receive a notification on a given channel.
     */
    public function shouldReceiveNotification(User $user, string $channel, string $notificationType = 'all'): bool
    {
        // 1. Check for specific notification type preference
        $specific = $user->notificationPreferences()
            ->where('channel', $channel)
            ->where('notification_type', $notificationType)
            ->first();

        if ($specific !== null) {
            return (bool) $specific->enabled;
        }

        // 2. Fall back to global channel preference ('all')
        if ($notificationType !== 'all') {
            $global = $user->notificationPreferences()
                ->where('channel', $channel)
                ->where('notification_type', 'all')
                ->first();

            if ($global !== null) {
                return (bool) $global->enabled;
            }
        }

        // 3. Default to true if no explicit preference recorded
        return true;
    }

    /**
     * Update or create notification preferences for a user.
     *
     * @param  array<int, array<string, mixed>>  $preferences
     */
    public function updatePreferences(User $user, array $preferences): void
    {
        foreach ($preferences as $preference) {
            $channel = (string) ($preference['channel'] ?? '');
            $type = (string) ($preference['notification_type'] ?? 'all');
            $enabled = (bool) ($preference['enabled'] ?? true);

            if ($channel === '') {
                continue;
            }

            $user->notificationPreferences()->updateOrCreate(
                [
                    'channel' => $channel,
                    'notification_type' => $type,
                ],
                [
                    'enabled' => $enabled,
                ]
            );
        }
    }
}
