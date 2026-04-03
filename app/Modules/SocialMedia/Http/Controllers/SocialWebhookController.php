<?php

namespace App\Modules\SocialMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chatbot\Services\ConversationBotPolicy;
use App\Modules\Conversations\Contracts\InboxMessageIngester;
use App\Modules\Conversations\Data\InboxMessageEnvelope;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\SocialMedia\Models\SocialAccountChatbotIntegration;
use App\Modules\SocialMedia\Services\MetaWebhookPayloadParser;
use App\Modules\SocialMedia\Services\XAccountActivityPayloadParser;
use App\Modules\SocialMedia\Services\XSocialAccountResolver;
use App\Modules\SocialMedia\Services\XWebhookSecurity;
use App\Modules\Conversations\Jobs\GenerateAiReply;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SocialWebhookController extends Controller
{
    public function __construct(
        private readonly InboxMessageIngester $ingester,
        private readonly ConversationBotPolicy $botPolicy,
        private readonly MetaWebhookPayloadParser $payloadParser,
        private readonly XWebhookSecurity $xWebhookSecurity,
        private readonly XAccountActivityPayloadParser $xPayloadParser,
        private readonly XSocialAccountResolver $xAccountResolver
    ) {
    }

    public function inbound(Request $request): JsonResponse
    {
        if (!$this->payloadParser->verifySignature($request)) {
            return response()->json(['message' => 'Invalid webhook signature'], Response::HTTP_UNAUTHORIZED);
        }

        $events = $this->payloadParser->parse($request);
        $processed = 0;
        $deduplicated = 0;

        foreach ($events as $event) {
            /** @var SocialAccount $account */
            $account = $event['account'];
            TenantContext::setCurrentId((int) $account->tenant_id);

            $result = $this->ingester->ingest(new InboxMessageEnvelope(
                channel: 'social_dm',
                instanceId: (int) $account->id,
                conversationExternalId: null,
                contactExternalId: $event['contact_id'],
                contactName: $event['contact_name'],
                direction: $event['direction'],
                type: 'text',
                body: $event['message'],
                externalMessageId: $event['external_message_id'],
                payload: $this->sanitizeWebhookPayload($event['payload']),
                conversationMetadata: ['platform' => $event['platform']],
                messageStatus: $event['direction'] === 'out' ? 'sent' : 'delivered',
                ingestionMode: InboxMessageEnvelope::MODE_REALTIME,
                incrementUnread: $event['direction'] !== 'out',
                writeActivityLog: false,
                broadcast: false,
            ));
            $processed++;

            if ($result->deduplicated) {
                $deduplicated++;
                continue;
            }

            $chatbot = $this->chatbotIntegration($account);
            if ($chatbot['auto_reply'] && $event['direction'] === 'in') {
                $decision = $this->evaluateAutoReplyDecision($result->conversation, $chatbot['chatbot_account_id'], (string) ($result->message->body ?? ''));
                if (($decision['action'] ?? null) === 'reply') {
                    GenerateAiReply::dispatch($result->conversation->id, $result->message->id, $chatbot['chatbot_account_id']);
                } elseif (($decision['action'] ?? null) === 'handoff') {
                    $this->markConversationHandoff($result->conversation, (string) ($decision['reason'] ?? 'user_requested_human'), $chatbot['chatbot_account_id']);
                }
            }

            if ($event['direction'] === 'in') {
                $account->updateOperationalMetadata([
                    'last_inbound_at' => now()->toDateTimeString(),
                    'last_inbound_summary' => mb_substr(trim((string) $event['message']), 0, 160),
                ]);
            }
        }

        return response()->json([
            'stored' => true,
            'processed' => $processed,
            'deduplicated' => $deduplicated > 0,
            'deduplicated_count' => $deduplicated,
        ]);
    }

    public function xCrc(Request $request): JsonResponse
    {
        $crcToken = trim((string) $request->query('crc_token', ''));
        abort_unless($crcToken !== '', 400, 'Missing crc_token');

        return response()->json($this->xWebhookSecurity->buildCrcResponse($crcToken));
    }

    public function xInbound(Request $request): JsonResponse
    {
        $signature = trim((string) $request->header('x-twitter-webhooks-signature', ''));
        if (!$this->xWebhookSecurity->verifySignature((string) $request->getContent(), $signature)) {
            return response()->json(['message' => 'Invalid X webhook signature'], Response::HTTP_UNAUTHORIZED);
        }

        $events = $this->xPayloadParser->parse($request->all());
        $processed = 0;
        $deduplicated = 0;

        foreach ($events as $event) {
            $account = $this->xAccountResolver->resolveByForUserId((string) ($event['for_user_id'] ?? ''));
            if (!$account) {
                continue;
            }

            TenantContext::setCurrentId((int) $account->tenant_id);

            $body = trim((string) ($event['text'] ?? ''));
            if ($body === '' && !empty($event['attachment_media_keys'])) {
                $body = '[x media attachment]';
            }

            $result = $this->ingester->ingest(new InboxMessageEnvelope(
                channel: 'social_dm',
                instanceId: (int) $account->id,
                conversationExternalId: (string) ($event['conversation_id'] ?? ''),
                contactExternalId: (string) $event['contact_id'],
                contactName: null,
                direction: (string) ($event['direction'] ?? 'in'),
                type: 'text',
                body: $body,
                externalMessageId: (string) ($event['event_id'] ?? null),
                payload: $this->sanitizeWebhookPayload($event),
                conversationMetadata: [
                    'platform' => 'x',
                    'x_dm_conversation_id' => $event['conversation_id'] ?? null,
                ],
                messageStatus: (($event['direction'] ?? 'in') === 'out') ? 'sent' : 'delivered',
                ingestionMode: InboxMessageEnvelope::MODE_REALTIME,
                incrementUnread: ($event['direction'] ?? 'in') !== 'out',
                writeActivityLog: false,
                broadcast: false,
            ));
            $processed++;

            if ($result->deduplicated) {
                $deduplicated++;
                continue;
            }

            if (($event['direction'] ?? 'in') === 'in') {
                $account->updateOperationalMetadata([
                    'last_inbound_at' => now()->toDateTimeString(),
                    'last_inbound_summary' => mb_substr($body, 0, 160),
                    'x_webhook_last_event_at' => now()->toDateTimeString(),
                    'x_webhook_last_event_id' => (string) ($event['event_id'] ?? ''),
                ]);

                $chatbot = $this->chatbotIntegration($account);
                if ($chatbot['auto_reply']) {
                    $decision = $this->evaluateAutoReplyDecision($result->conversation, $chatbot['chatbot_account_id'], (string) ($result->message->body ?? ''));
                    if (($decision['action'] ?? null) === 'reply') {
                        GenerateAiReply::dispatch($result->conversation->id, $result->message->id, $chatbot['chatbot_account_id']);
                    } elseif (($decision['action'] ?? null) === 'handoff') {
                        $this->markConversationHandoff($result->conversation, (string) ($decision['reason'] ?? 'user_requested_human'), $chatbot['chatbot_account_id']);
                    }
                }
            }
        }

        return response()->json([
            'stored' => true,
            'processed' => $processed,
            'deduplicated' => $deduplicated > 0,
            'deduplicated_count' => $deduplicated,
        ]);
    }

    // Meta challenge verify
    public function verify(Request $request)
    {
        $verifyToken = config('services.meta.verify_token', 'changeme');
        if ($request->get('hub_verify_token') === $verifyToken) {
            return response((string) $request->get('hub_challenge'), Response::HTTP_OK)
                ->header('Content-Type', 'text/plain');
        }
        return response()->json(['message' => 'Invalid verify token'], Response::HTTP_FORBIDDEN);
    }

    private function chatbotIntegration(SocialAccount $account): array
    {
        $fallback = [
            'auto_reply' => (bool) ($account->auto_reply ?? false),
            'chatbot_account_id' => $account->chatbot_account_id ? (int) $account->chatbot_account_id : null,
        ];

        if (!Schema::hasTable('social_account_chatbot_integrations')) {
            return $fallback;
        }

        /** @var SocialAccountChatbotIntegration|null $integration */
        $integration = $account->chatbotIntegration()->first();
        if (!$integration) {
            return $fallback;
        }

        return [
            'auto_reply' => (bool) $integration->auto_reply,
            'chatbot_account_id' => $integration->chatbot_account_id ? (int) $integration->chatbot_account_id : null,
        ];
    }

    private function evaluateAutoReplyDecision(Conversation $conversation, ?int $chatbotAccountId, string $incomingBody): array
    {
        if (!$chatbotAccountId) {
            return ['action' => 'skip', 'reason' => 'no_chatbot_account'];
        }

        $account = $this->resolveChatbotAccount($chatbotAccountId);
        if (!$account) {
            return ['action' => 'skip', 'reason' => 'chatbot_not_found'];
        }

        if (method_exists($account, 'usesAi') && !$account->usesAi()) {
            return ['action' => 'skip', 'reason' => 'rule_only'];
        }

        return $this->botPolicy->evaluateInbound($conversation, $account, $incomingBody);
    }

    private function resolveChatbotAccount(int $chatbotAccountId)
    {
        $chatbotClass = \App\Modules\Chatbot\Models\ChatbotAccount::class;
        if (!class_exists($chatbotClass) || !Schema::hasTable('chatbot_accounts')) {
            return null;
        }

        $hasAccessScope = Schema::hasColumn('chatbot_accounts', 'access_scope');
        return $chatbotClass::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('status', 'active')
            ->when($hasAccessScope, fn ($query) => $query->where('access_scope', 'public'))
            ->find($chatbotAccountId);
    }

    private function markConversationHandoff(Conversation $conversation, string $reason, ?int $chatbotAccountId = null): void
    {
        $account = $chatbotAccountId ? $this->resolveChatbotAccount($chatbotAccountId) : null;
        $this->botPolicy->pauseForHuman($conversation, $reason, $account);
    }

    private function sanitizeWebhookPayload(array $payload): array
    {
        return $this->maskSensitiveValues($payload, [
            'token',
            'access_token',
            'verify_token',
            'app_secret',
            'authorization',
            'signature',
            'signature_key',
        ]);
    }

    private function maskSensitiveValues(array $payload, array $sensitiveKeys): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->maskSensitiveValues($value, $sensitiveKeys);
                continue;
            }

            $normalizedKey = mb_strtolower((string) $key);
            if (in_array($normalizedKey, $sensitiveKeys, true)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
