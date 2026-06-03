<?php

namespace App\Support\Notifications;

use App\Multitenancy\QueryContextGuard;
use App\Models\CoreNotification;
use App\Models\NotificationRecipient;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\DB;

class NotificationCenter
{
    private NotificationTypeRegistry $registry;
    private NotificationRecipientResolver $recipients;
    private NotificationUrlBuilder $urlBuilder;
    private NotificationDeliveryDispatcher $dispatcher;
    private QueryContextGuard $guard;

    public function __construct(
        NotificationTypeRegistry $registry,
        NotificationRecipientResolver $recipients,
        NotificationUrlBuilder $urlBuilder,
        NotificationDeliveryDispatcher $dispatcher,
        QueryContextGuard $guard
    ) {
        $this->registry = $registry;
        $this->recipients = $recipients;
        $this->urlBuilder = $urlBuilder;
        $this->dispatcher = $dispatcher;
        $this->guard = $guard;
    }

    public function publish(NotificationMessage|array $payload): CoreNotification
    {
        $message = is_array($payload) ? NotificationMessage::fromArray($payload) : $payload;
        $definition = $this->registry->normalize($message);

        return DB::transaction(function () use ($message, $definition): CoreNotification {
            $tenantId = $message->tenantId ?: $this->guard->requireTenant('notification publish');
            $companyId = $message->companyId ?: CompanyContext::currentId();
            $actions = $this->urlBuilder->normalize($message->actions);
            $now = now();

            $notification = $this->findExisting($tenantId, $companyId, $message->branchId, $message->dedupeKey);

            if ($notification) {
                $notification->fill([
                    'severity' => $definition['severity'],
                    'title' => $definition['title'],
                    'body' => $definition['body'],
                    'actions' => $actions,
                    'meta' => $message->meta,
                    'occurred_at' => $message->occurredAt ?: $now,
                    'last_seen_at' => $now,
                    'occurrence_count' => (int) $notification->occurrence_count + 1,
                    'status' => 'active',
                    'dismissed_at' => null,
                    'resolved_at' => null,
                ])->save();
            } else {
                $notification = CoreNotification::query()->create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'branch_id' => $message->branchId,
                    'module' => $message->module,
                    'type' => $message->type,
                    'severity' => $definition['severity'],
                    'status' => 'active',
                    'title' => $definition['title'],
                    'body' => $definition['body'],
                    'resource_type' => $message->resourceType,
                    'resource_id' => $message->resourceId,
                    'dedupe_key' => $message->dedupeKey,
                    'actions' => $actions,
                    'meta' => $message->meta,
                    'occurred_at' => $message->occurredAt ?: $now,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                    'occurrence_count' => 1,
                ]);
            }

            $resolvedRecipients = $this->recipients->resolve($message, $definition);

            foreach ($resolvedRecipients as $user) {
                $recipient = NotificationRecipient::query()->firstOrCreate(
                    [
                        'notification_id' => $notification->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'tenant_id' => $tenantId,
                        'company_id' => $companyId,
                        'branch_id' => $message->branchId,
                        'is_read' => false,
                    ]
                );

                if ($recipient->exists && $recipient->wasRecentlyCreated === false) {
                    $recipient->forceFill([
                        'is_read' => false,
                        'read_at' => null,
                        'dismissed_at' => null,
                    ])->save();
                }

                $this->dispatcher->dispatch($notification, $recipient, $user, $definition['channels']);
            }

            return $notification->fresh(['recipients', 'deliveries']);
        });
    }

    private function findExisting(int $tenantId, ?int $companyId, ?int $branchId, ?string $dedupeKey): ?CoreNotification
    {
        if (!$dedupeKey) {
            return null;
        }

        return CoreNotification::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('dedupe_key', $dedupeKey)
            ->where('status', 'active')
            ->lockForUpdate()
            ->latest('id')
            ->first();
    }
}
