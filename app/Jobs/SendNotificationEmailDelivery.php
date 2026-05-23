<?php

namespace App\Jobs;

use App\Mail\CoreNotificationMail;
use App\Models\NotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmailDelivery implements ShouldQueue
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

        $user = optional($delivery->recipient)->user;
        if (!$user || empty($user->email)) {
            $delivery->update([
                'status' => NotificationDelivery::STATUS_SKIPPED,
                'meta' => array_merge($delivery->meta ?? [], ['reason' => 'missing_email']),
            ]);

            return;
        }

        try {
            Mail::to($user->email)->send(new CoreNotificationMail($delivery->notification, $user));

            $delivery->update([
                'status' => NotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $delivery->update([
                'status' => NotificationDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
