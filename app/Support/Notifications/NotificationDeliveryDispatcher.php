<?php

namespace App\Support\Notifications;

use App\Jobs\SendNotificationEmailDelivery;
use App\Jobs\SendNotificationPushDelivery;
use App\Models\CoreNotification;
use App\Models\NotificationDelivery;
use App\Models\NotificationPushSubscription;
use App\Models\NotificationRecipient;
use App\Models\User;

class NotificationDeliveryDispatcher
{
    public function __construct(
        private readonly NotificationPreferenceService $preferences,
    ) {
    }

    /**
     * @param  array<int, string>  $channels
     */
    public function dispatch(CoreNotification $notification, NotificationRecipient $recipient, User $user, array $channels): void
    {
        foreach ($channels as $channel) {
            if ($channel === 'in_app') {
                continue;
            }

            if (!$this->preferences->allows($user, $notification->type, $channel, $notification->severity)) {
                $this->createDelivery($notification, $recipient, $user, $channel, NotificationDelivery::STATUS_SKIPPED, [
                    'reason' => 'preference_disabled',
                ]);
                continue;
            }

            if ($channel === 'web_push' && !$this->hasActivePushSubscription($user)) {
                $this->createDelivery($notification, $recipient, $user, $channel, NotificationDelivery::STATUS_SKIPPED, [
                    'reason' => 'missing_push_subscription',
                ]);
                continue;
            }

            if ($channel === 'email' && empty($user->email)) {
                $this->createDelivery($notification, $recipient, $user, $channel, NotificationDelivery::STATUS_SKIPPED, [
                    'reason' => 'missing_email',
                ]);
                continue;
            }

            $delivery = $this->createDelivery($notification, $recipient, $user, $channel, NotificationDelivery::STATUS_PENDING, [
                'severity' => $notification->severity,
            ]);

            if ($channel === 'web_push') {
                SendNotificationPushDelivery::dispatch($delivery->id);
            }

            if ($channel === 'email') {
                SendNotificationEmailDelivery::dispatch($delivery->id);
            }
        }
    }

    private function hasActivePushSubscription(User $user): bool
    {
        return NotificationPushSubscription::query()
            ->where('tenant_id', (int) $user->tenant_id)
            ->where('user_id', $user->id)
            ->active()
            ->exists();
    }

    private function createDelivery(
        CoreNotification $notification,
        NotificationRecipient $recipient,
        User $user,
        string $channel,
        string $status,
        array $meta = []
    ): NotificationDelivery {
        return NotificationDelivery::query()->create([
            'notification_id' => $notification->id,
            'notification_recipient_id' => $recipient->id,
            'tenant_id' => $notification->tenant_id,
            'company_id' => $notification->company_id,
            'branch_id' => $notification->branch_id,
            'user_id' => $user->id,
            'channel' => $channel,
            'status' => $status,
            'meta' => $meta,
            'queued_at' => $status === NotificationDelivery::STATUS_PENDING ? now() : null,
            'sent_at' => $status === NotificationDelivery::STATUS_SENT ? now() : null,
            'failed_at' => $status === NotificationDelivery::STATUS_FAILED ? now() : null,
        ]);
    }
}
