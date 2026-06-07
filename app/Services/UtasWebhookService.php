<?php

namespace App\Services;

use App\Contracts\UtasWebhookNotificationSender;
use App\Services\Webhooks\WebhookReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UtasWebhookService
{
    private const SUPPORTED_STATES = [
        'paid',
        'complete',
    ];

    public function __construct(
        private readonly UtasWebhookNotificationSender $notificationSender,
        private readonly WebhookReceiptService $receipts,
        private readonly PlatformActivityRecorder $activity,
        private readonly PlatformEventBus $events
    ) {
    }

    public function isAuthorized(Request $request): bool
    {
        $secret = trim((string) config('services.utas.webhook_secret', ''));
        if ($secret === '') {
            return app()->environment(['local', 'testing']);
        }

        $provided = trim((string) ($request->header('X-Webhook-Secret') ?: $request->query('secret', '')));

        return $provided !== '' && hash_equals($secret, $provided);
    }

    /**
     * @return array{handled: bool, state: string, notified: bool}
     */
    public function handle(Request $request): array
    {
        $payload = $request->all();
        $receipt = $this->receipts->receive('utas', 'webhooks.utas', $request, $payload);

        $validator = Validator::make($payload, [
            'state' => ['required', 'string'],
            'name' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'store' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'store_link' => ['nullable', 'string'],
            'total' => ['nullable'],
        ]);

        if ($validator->fails()) {
            $this->receipts->markFailed($receipt, 'validation_failed', ['errors' => $validator->errors()->toArray()]);
            throw new ValidationException($validator);
        }

        $state = $this->normalizeState((string) ($payload['state'] ?? ''));
        if ($state === '') {
            $this->receipts->markFailed($receipt, 'invalid_state');
            throw ValidationException::withMessages([
                'state' => 'Webhook UTAS wajib memiliki state yang valid.',
            ]);
        }

        $handled = in_array($state, self::SUPPORTED_STATES, true);
        if (!$handled) {
            $this->receipts->markProcessed($receipt, ['handled' => false, 'state' => $state]);
            Log::info('UTAS webhook ignored', [
                'state' => $state,
                'store' => $this->nullableString($payload['store'] ?? null),
            ]);

            return [
                'handled' => false,
                'state' => $state,
                'notified' => false,
            ];
        }

        $notified = false;

        if ($state === 'paid') {
            $notifyEmail = $this->notifyEmail();
            if ($notifyEmail === null) {
                throw ValidationException::withMessages([
                    'notify_email' => 'UTAS webhook email tujuan belum dikonfigurasi.',
                ]);
            }

            $this->notificationSender->sendPaidNotification($notifyEmail, $payload);
            $notified = true;
        }

        $this->receipts->markSignature($receipt, true);
        $this->receipts->markProcessed($receipt, [
            'handled' => true,
            'state' => $state,
            'notified' => $notified,
        ]);
        $this->activity->record(
            'core',
            'webhook.utas.' . $state,
            \App\Models\PlatformWebhookReceipt::class,
            $receipt->id,
            'UTAS webhook ' . $state,
            [
                'state' => $state,
                'notified' => $notified,
            ]
        );
        $this->events->publish(
            'platform.webhook.utas.' . $state,
            \App\Models\PlatformWebhookReceipt::class,
            $receipt->id,
            [
                'state' => $state,
                'notified' => $notified,
            ],
            'utas:' . $receipt->dedupe_key
        );

        Log::info('UTAS webhook handled', [
            'state' => $state,
            'store' => $this->nullableString($payload['store'] ?? null),
            'buyer_email' => $this->maskEmail($this->nullableString($payload['email'] ?? null)),
            'notified' => $notified,
        ]);

        return [
            'handled' => true,
            'state' => $state,
            'notified' => $notified,
        ];
    }

    public function markUnauthorized(Request $request): void
    {
        $receipt = $this->receipts->receive('utas', 'webhooks.utas', $request, $request->all());
        $this->receipts->markSignature($receipt, false);
    }

    private function normalizeState(string $state): string
    {
        $normalized = strtolower(trim($state));

        return match ($normalized) {
            'complete order', 'complete_order' => 'complete',
            'new order', 'new_order' => 'order',
            default => $normalized,
        };
    }

    private function notifyEmail(): ?string
    {
        $email = trim((string) config('services.utas.notify_email', ''));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function maskEmail(?string $email): ?string
    {
        if ($email === null || !str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, 2);

        return $visible . str_repeat('*', max(strlen($local) - 2, 1)) . '@' . $domain;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
