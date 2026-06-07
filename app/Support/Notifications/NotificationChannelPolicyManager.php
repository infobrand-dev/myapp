<?php

namespace App\Support\Notifications;

use App\Models\User;

class NotificationChannelPolicyManager
{
    /**
     * @return array{allowed: bool, reason: string|null}
     */
    public function evaluate(User $user, string $channel, string $type, string $severity): array
    {
        $allowedChannels = (array) config('platform-core.notifications.channels', []);

        if (!in_array($channel, $allowedChannels, true)) {
            return ['allowed' => false, 'reason' => 'channel_not_supported'];
        }

        if ($channel === 'future_whatsapp') {
            return ['allowed' => false, 'reason' => 'channel_not_implemented'];
        }

        return ['allowed' => true, 'reason' => null];
    }
}
