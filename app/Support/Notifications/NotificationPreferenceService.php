<?php

namespace App\Support\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferenceService
{
    public function allows(User $user, string $type, string $channel, string $severity): bool
    {
        if ($channel === 'in_app') {
            return true;
        }

        $preference = NotificationPreference::query()
            ->where('tenant_id', (int) $user->tenant_id)
            ->where('user_id', $user->id)
            ->where('notification_type', $type)
            ->where('channel', $channel)
            ->orderByDesc('company_id')
            ->first();

        if ($preference) {
            return (bool) $preference->is_enabled;
        }

        return in_array($channel, config('notifications.channel_defaults.' . $severity, ['in_app']), true);
    }
}
