<?php

namespace App\Modules\SocialMedia\Services;

use App\Modules\SocialMedia\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MetaWebhookPayloadParser
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(Request $request): array
    {
        $payload = $request->all();

        if ($this->looksLikeLegacyPayload($payload)) {
            return [$this->parseLegacyPayload($payload)];
        }

        return $this->parseMetaPayload($request);
    }

    public function verifySignature(Request $request): bool
    {
        $appSecret = trim((string) config('services.meta.app_secret'));
        $header = trim((string) $request->header('X-Hub-Signature-256', ''));

        if ($appSecret === '' || $header === '') {
            return true;
        }

        if (!str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', (string) $request->getContent(), $appSecret);

        return hash_equals($expected, $header);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function parseLegacyPayload(array $payload): array
    {
        $validated = Validator::make($payload, [
            'token' => ['required', 'string'],
            'platform' => ['required', 'in:instagram,facebook'],
            'contact_id' => ['required', 'string'],
            'contact_name' => ['nullable', 'string'],
            'message' => ['required', 'string'],
            'external_message_id' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:in,out'],
            'account_id' => ['nullable', 'integer'],
        ])->validate();

        $token = trim((string) ($validated['token'] ?? ''));
        $account = SocialAccount::query()
            ->where('status', 'active')
            ->where('platform', $validated['platform'])
            ->when($validated['account_id'] ?? null, fn ($q) => $q->where('id', $validated['account_id']))
            ->where(function ($query) use ($token) {
                $query->where('access_token_hash', hash('sha256', $token))
                    ->orWhere('access_token', $token);
            })
            ->get()
            ->first(fn (SocialAccount $candidate) => hash_equals((string) $candidate->access_token, $token));

        if (!$account) {
            throw new HttpException(401, 'Invalid token/account');
        }

        return [
            'account' => $account,
            'platform' => $validated['platform'],
            'contact_id' => (string) $validated['contact_id'],
            'contact_name' => $validated['contact_name'] ?? null,
            'message' => (string) $validated['message'],
            'external_message_id' => $validated['external_message_id'] ?? null,
            'direction' => (string) ($validated['direction'] ?? 'in'),
            'payload' => $payload,
            'account_id' => $account->id,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseMetaPayload(Request $request): array
    {
        $payload = $request->all();

        $validated = Validator::make($payload, [
            'object' => ['required', 'string', 'in:page,instagram'],
            'entry' => ['required', 'array'],
        ])->validate();

        $events = collect();

        foreach ((array) ($validated['entry'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            foreach ((array) ($entry['messaging'] ?? []) as $messaging) {
                if (!is_array($messaging)) {
                    continue;
                }

                $normalized = $this->normalizeMetaMessagingEvent((string) $validated['object'], $entry, $messaging);
                if ($normalized !== null) {
                    $events->push($normalized);
                }
            }
        }

        if ($events->isEmpty()) {
            throw ValidationException::withMessages([
                'payload' => 'Webhook Meta tidak mengandung event pesan yang dapat diproses.',
            ]);
        }

        return $events->values()->all();
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>  $messaging
     * @return array<string, mixed>|null
     */
    private function normalizeMetaMessagingEvent(string $object, array $entry, array $messaging): ?array
    {
        if (!isset($messaging['sender']['id']) || !isset($messaging['recipient']['id'])) {
            return null;
        }

        if ($this->isDeliveryOrReadEvent($messaging)) {
            return null;
        }

        $recipientId = trim((string) Arr::get($messaging, 'recipient.id', ''));
        $entryId = trim((string) Arr::get($entry, 'id', ''));
        $account = $this->resolveMetaAccount($object, $recipientId, $entryId);

        if (!$account) {
            return null;
        }

        $direction = (bool) Arr::get($messaging, 'message.is_echo', false) ? 'out' : 'in';
        $message = $this->extractMessageText($messaging);

        if ($message === null || trim($message) === '') {
            return null;
        }

        return [
            'account' => $account,
            'platform' => $account->platform,
            'contact_id' => trim((string) Arr::get($messaging, 'sender.id')),
            'contact_name' => null,
            'message' => $message,
            'external_message_id' => Arr::get($messaging, 'message.mid')
                ?: Arr::get($messaging, 'postback.mid')
                ?: Arr::get($messaging, 'mid'),
            'direction' => $direction,
            'payload' => [
                'object' => $object,
                'entry' => $entry,
                'messaging' => $messaging,
            ],
            'account_id' => $account->id,
        ];
    }

    /**
     * @return string|null
     */
    private function extractMessageText(array $messaging): ?string
    {
        $text = trim((string) Arr::get($messaging, 'message.text', ''));
        if ($text !== '') {
            return $text;
        }

        $quickReplyPayload = trim((string) Arr::get($messaging, 'message.quick_reply.payload', ''));
        if ($quickReplyPayload !== '') {
            return $quickReplyPayload;
        }

        $postbackTitle = trim((string) Arr::get($messaging, 'postback.title', ''));
        if ($postbackTitle !== '') {
            return $postbackTitle;
        }

        $postbackPayload = trim((string) Arr::get($messaging, 'postback.payload', ''));
        if ($postbackPayload !== '') {
            return $postbackPayload;
        }

        $attachments = collect((array) Arr::get($messaging, 'message.attachments', []))
            ->filter(fn ($attachment) => is_array($attachment))
            ->map(fn (array $attachment) => trim((string) ($attachment['type'] ?? 'attachment')))
            ->filter()
            ->values();

        if ($attachments->isNotEmpty()) {
            $labels = $attachments
                ->map(fn (string $type) => '[' . strtolower($type) . ' attachment]')
                ->implode(' ');

            return $labels !== '' ? $labels : '[attachment]';
        }

        return null;
    }

    private function isDeliveryOrReadEvent(array $messaging): bool
    {
        return isset($messaging['delivery']) || isset($messaging['read']);
    }

    private function resolveMetaAccount(string $object, string $recipientId, string $entryId): ?SocialAccount
    {
        $ids = array_values(array_unique(array_filter([$recipientId, $entryId])));
        if ($ids === []) {
            return null;
        }

        $query = SocialAccount::query()->where('status', 'active');

        if ($object === 'instagram') {
            return $query
                ->where(function ($builder) use ($ids) {
                    $builder->whereIn('ig_business_id', $ids)
                        ->orWhereIn('page_id', $ids);
                })
                ->orderByRaw("CASE WHEN platform = 'instagram' THEN 0 ELSE 1 END")
                ->first();
        }

        return $query
            ->where(function ($builder) use ($ids) {
                $builder->whereIn('page_id', $ids)
                    ->orWhereIn('ig_business_id', $ids);
            })
            ->orderByRaw("CASE WHEN platform = 'facebook' THEN 0 ELSE 1 END")
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function looksLikeLegacyPayload(array $payload): bool
    {
        return isset($payload['token'], $payload['platform'], $payload['contact_id'], $payload['message']);
    }
}
