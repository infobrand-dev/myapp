<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Models\NotificationPushSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class SendNotificationPushDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $deliveryId)
    {
    }

    public function handle(): void
    {
        $delivery = NotificationDelivery::query()->with(['notification', 'recipient.user'])->find($this->deliveryId);

        if (!$delivery || $delivery->status !== NotificationDelivery::STATUS_PENDING) {
            return;
        }

        $subscriptions = NotificationPushSubscription::query()
            ->where('tenant_id', $delivery->tenant_id)
            ->where('user_id', $delivery->user_id)
            ->where('is_active', true)
            ->get();

        if ($subscriptions->isEmpty()) {
            $delivery->update([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'meta' => array_merge($delivery->meta ?? [], ['reason' => 'missing_push_subscription']),
            ]);

            return;
        }

        $vapid = config('notifications.push.vapid', []);
        if (empty($vapid['public_key']) || empty($vapid['private_key']) || empty($vapid['subject'])) {
            $delivery->update([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'meta' => array_merge($delivery->meta ?? [], ['reason' => 'missing_vapid_config']),
            ]);

            return;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $vapid['subject'],
                    'publicKey' => $vapid['public_key'],
                    'privateKey' => $vapid['private_key'],
                ],
            ]);

            $payload = json_encode([
                'title' => $delivery->notification->title,
                'body' => $delivery->notification->body,
                'tag' => 'notif-' . $delivery->notification_id,
                'url' => data_get($delivery->notification->actions, '0.url', route('notifications.index')),
            ]);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
                    ]),
                    $payload
                );
            }

            $reports = $webPush->flush();
            $hasFailure = false;

            foreach ($reports as $report) {
                if (!$report->isSuccess()) {
                    $hasFailure = true;
                    break;
                }
            }

            $delivery->update([
                'status' => $hasFailure ? NotificationDelivery::STATUS_FAILED : NotificationDelivery::STATUS_SENT,
                'sent_at' => $hasFailure ? null : now(),
                'failed_at' => $hasFailure ? now() : null,
                'error_message' => $hasFailure ? 'Sebagian atau seluruh web push gagal dikirim.' : null,
            ]);

            if (!$hasFailure) {
                NotificationPushSubscription::query()
                    ->whereIn('id', $subscriptions->pluck('id'))
                    ->update(['last_used_at' => now()]);
            }
        } catch (\Throwable $e) {
            $delivery->update([
                'status' => NotificationDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
