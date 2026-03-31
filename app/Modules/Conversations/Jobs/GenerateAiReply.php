<?php

namespace App\Modules\Conversations\Jobs;

use App\Modules\Chatbot\Services\ConversationBotPolicy;
use App\Modules\Chatbot\Services\RagContextBuilder;
use App\Modules\Conversations\Contracts\ConversationAiAssistantRegistry;
use App\Modules\Conversations\Contracts\ConversationChannelManager;
use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Services\AiUsageService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateAiReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const HISTORY_LIMIT = 12;
    private const HISTORY_ITEM_MAX_CHARS = 700;
    private const ROLLING_SUMMARY_LINES = 8;
    public int $conversationId;
    public int $messageId;
    public ?int $chatbotAccountId;
    private const RESPONSE_CACHE_TTL_SECONDS = 600;
    private const REQUEST_LOCK_TTL_SECONDS = 45;

    public function __construct(int $conversationId, int $messageId, ?int $chatbotAccountId = null)
    {
        $this->conversationId = $conversationId;
        $this->messageId = $messageId;
        $this->chatbotAccountId = $chatbotAccountId;
    }

    public function handle(): void
    {
        $conversation = Conversation::query()
            ->where('tenant_id', $this->tenantId())
            ->find($this->conversationId);
        $incoming = ConversationMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->find($this->messageId);
        if (!$conversation || !$incoming || $incoming->direction !== 'in') return;

        if (!$this->acquireRequestLock()) {
            Log::info('AI reply skipped: duplicate in-flight request', [
                'conversation_id' => $this->conversationId,
                'message_id' => $this->messageId,
            ]);
            return;
        }

        $aiAccount = app(ConversationAiAssistantRegistry::class)->resolveAccount($this->chatbotAccountId);
        if (!$aiAccount) {
            Log::warning('AI reply skipped: chatbot module not ready', ['conversation_id' => $this->conversationId]);
            return;
        }

        if (!$aiAccount || !$aiAccount->api_key) {
            Log::warning('AI reply skipped: no active chatbot account', ['conversation_id' => $this->conversationId]);
            return;
        }

        if (method_exists($aiAccount, 'usesAi') && !$aiAccount->usesAi()) {
            Log::info('AI reply skipped: chatbot account is configured as rule-only', [
                'conversation_id' => $this->conversationId,
                'chatbot_account_id' => $aiAccount->id,
            ]);
            return;
        }

        if (!app(AiUsageService::class)->hasCreditsRemaining($this->tenantId())) {
            Log::info('AI reply skipped: tenant AI credits exhausted', [
                'conversation_id' => $this->conversationId,
                'tenant_id' => $this->tenantId(),
            ]);
            app(ConversationBotPolicy::class)->markSkipped($conversation, $aiAccount, 'ai_credits_exhausted');
            return;
        }

        $policy = app(ConversationBotPolicy::class);
        $inboundDecision = $policy->evaluateInbound($conversation, $aiAccount, (string) ($incoming->body ?? ''), (array) ($incoming->payload ?? []));
        if (($inboundDecision['action'] ?? 'skip') !== 'reply') {
            if (($inboundDecision['action'] ?? null) === 'handoff') {
                $policy->pauseForHuman($conversation, (string) ($inboundDecision['reason'] ?? 'user_requested_human'), $aiAccount);
            } else {
                $policy->markSkipped($conversation, $aiAccount, (string) ($inboundDecision['reason'] ?? 'ineligible'));
            }

            return;
        }

        [$history, $rollingSummary] = $this->buildPromptHistory($conversation);
        $ragContexts = [];
        if (method_exists($aiAccount, 'usesAi') && $aiAccount->rag_enabled) {
            $ragContexts = app(RagContextBuilder::class)->retrieve($aiAccount, (string) ($incoming->body ?? ''));
        }

        $systemMessages = [
            ['role' => 'system', 'content' => $this->systemPrompt($conversation, $aiAccount)],
        ];
        if ($rollingSummary !== null && trim($rollingSummary) !== '') {
            $systemMessages[] = [
                'role' => 'system',
                'content' => "Ringkasan konteks sebelumnya:\n{$rollingSummary}",
            ];
        }
        if (!empty($ragContexts)) {
            $contextText = collect($ragContexts)->map(function ($ctx, $index) {
                $n = $index + 1;
                return "[S{$n}] " . ($ctx['title'] ?? 'Dokumen') . "\n" . ($ctx['content'] ?? '');
            })->implode("\n\n");

            $systemMessages[] = [
                'role' => 'system',
                'content' => "Gunakan knowledge base berikut jika relevan. Jika konteks tidak cukup, jangan mengarang dan prioritaskan serahkan ke tim.\n\n{$contextText}",
            ];
        }

        $payload = [
            'model' => $aiAccount->model ?: config('services.openai.model', 'gpt-4o-mini'),
            'messages' => array_merge($systemMessages, $history),
            'max_tokens' => 200,
            'temperature' => 0.5,
        ];

        $cacheKey = $this->responseCacheKey((int) $aiAccount->id, $payload);
        $usage = null;
        try {
            $reply = Cache::get($cacheKey);

            if ($reply === null) {
                $response = Http::withToken($aiAccount->api_key)
                    ->timeout(10)
                    ->post('https://api.openai.com/v1/chat/completions', $payload);
                $reply = $response->successful()
                    ? ($response->json('choices.0.message.content') ?? null)
                    : null;
                $usage = $response->successful() ? $response->json('usage') : null;

                if (is_string($reply) && trim($reply) !== '') {
                    Cache::put($cacheKey, $reply, now()->addSeconds(self::RESPONSE_CACHE_TTL_SECONDS));
                }
            }
        } catch (\Throwable $e) {
            Log::error('AI request failed', ['error' => $e->getMessage()]);
            $policy->markError($conversation, $aiAccount, 'ai_error', [
                'incoming_message_id' => $incoming->id,
                'channel' => $conversation->channel,
            ]);
            $reply = null;
        }

        $evaluation = $policy->evaluateReplyCandidate($conversation, $aiAccount, (string) $reply, $ragContexts);
        $knowledgeChunkIds = array_values(array_filter(array_map(fn (array $ctx) => $ctx['chunk_id'] ?? null, $ragContexts)));
        $knowledgeDocumentIds = array_values(array_filter(array_map(fn (array $ctx) => $ctx['document_id'] ?? null, $ragContexts)));
        $decisionMetadata = [
            'incoming_message_id' => $incoming->id,
            'channel' => $conversation->channel,
            'knowledge_chunk_ids' => $knowledgeChunkIds,
            'knowledge_document_ids' => $knowledgeDocumentIds,
            'confidence_score' => $evaluation['confidence_score'] ?? null,
        ];

        if (($evaluation['action'] ?? 'handoff') !== 'reply') {
            $reason = (string) ($evaluation['reason'] ?? 'ai_error');
            $policy->pauseForHuman($conversation, $reason, $aiAccount, $decisionMetadata);

            if (!($evaluation['send_handoff_ack'] ?? false)) {
                return;
            }

            $reply = $this->humanHandoffAcknowledgementMessage();
        }

        if (!$reply) {
            $reply = $this->humanHandoffAcknowledgementMessage();
        }

        $outgoing = $this->buildOutgoingReply((string) $reply, $conversation);

        $outboundDefaults = app(ConversationChannelManager::class)->outboundPersistenceDefaults($conversation);

        $replyMessage = ConversationMessage::create([
            'tenant_id' => $this->tenantId(),
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'direction' => 'out',
            'type' => $outgoing['type'],
            'body' => $outgoing['body'],
            'payload' => array_filter([
                'message_payload' => $outgoing['payload'],
                'reply_source' => 'ai_auto_reply',
                'confidence_score' => $evaluation['confidence_score'] ?? null,
                'knowledge_chunk_ids' => $knowledgeChunkIds,
                'knowledge_document_ids' => $knowledgeDocumentIds,
                'fallback_reason' => ($evaluation['action'] ?? 'reply') === 'reply' ? null : ($evaluation['reason'] ?? null),
            ]),
            'status' => $outboundDefaults['status'],
            'sent_at' => $outboundDefaults['sent_at'],
        ]);

        if (is_array($usage ?? null) && (int) ($usage['total_tokens'] ?? 0) > 0) {
            app(AiUsageService::class)->recordUsage([
                'tenant_id' => $this->tenantId(),
                'source_module' => 'conversations',
                'source_type' => 'auto_reply',
                'source_id' => $conversation->id,
                'chatbot_account_id' => $aiAccount->id,
                'provider' => $aiAccount->provider,
                'model' => $aiAccount->model ?: config('services.openai.model', 'gpt-4o-mini'),
                'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
                'metadata' => [
                    'incoming_message_id' => $incoming->id,
                    'reply_message_id' => $replyMessage->id,
                    'channel' => $conversation->channel,
                    'knowledge_document_ids' => $knowledgeDocumentIds,
                ],
            ]);
        }

        $policy->markReplySent($conversation, $aiAccount, isset($evaluation['confidence_score']) ? (float) $evaluation['confidence_score'] : null, array_merge($decisionMetadata, [
            'reply_message_id' => $replyMessage->id,
        ]));

        app(ConversationOutboundDispatcher::class)->dispatch($replyMessage);
    }

    private function systemPrompt(Conversation $conversation, $aiAccount): string
    {
        $base = trim((string) ($aiAccount->system_prompt ?? ''));
        if ($base === '') {
            $base = 'Kamu adalah asisten CS singkat, jelas, sopan, dan berbahasa Indonesia.';
        }

        $focusScope = trim((string) ($aiAccount->focus_scope ?? ''));
        if ($focusScope !== '') {
            $base .= "\nBatasan fokus: {$focusScope}";
        }

        if ($conversation->channel !== 'wa_api' || !$this->supportsInteractiveButtons($conversation) || !method_exists($aiAccount, 'allowInteractiveButtons') || !$aiAccount->allowInteractiveButtons()) {
            return $base;
        }

        return $base . ' Jika relevan, kamu boleh menambahkan tombol cepat WhatsApp dengan format JSON berikut TANPA teks lain: '
            . '{"text":"teks balasan","buttons":[{"id":"id_opsi_1","title":"Label 1"},{"id":"id_opsi_2","title":"Label 2"}]}. '
            . 'Gunakan max 3 tombol. Jika user ingin terhubung ke manusia, admin, agent, operator, tim event, atau customer service, wajib gunakan tombol dengan id "handoff_human". '
            . 'Contoh: {"text":"Saya bisa hubungkan Anda ke tim kami.","buttons":[{"id":"handoff_human","title":"Hubungi Sekarang"}]}. '
            . 'Jika tidak perlu tombol, jawab teks biasa.';
    }

    private function buildOutgoingReply(string $rawReply, Conversation $conversation): array
    {
        $fallbackText = trim($rawReply) !== '' ? trim($rawReply) : 'Terima kasih, pesan Anda sudah kami terima.';

        if ($conversation->channel !== 'wa_api') {
            return [
                'type' => 'text',
                'body' => $fallbackText,
                'payload' => null,
            ];
        }

        if (!$this->supportsInteractiveButtons($conversation)) {
            $structured = $this->parseStructuredReply($rawReply);

            return [
                'type' => 'text',
                'body' => $structured['text'] ?? $fallbackText,
                'payload' => null,
            ];
        }

        $structured = $this->parseStructuredReply($rawReply);
        if (!$structured) {
            return [
                'type' => 'text',
                'body' => $fallbackText,
                'payload' => null,
            ];
        }

        return [
            'type' => 'interactive',
            'body' => $structured['text'],
            'payload' => [
                'interactive' => [
                    'type' => 'button',
                    'body' => ['text' => $structured['text']],
                    'action' => ['buttons' => $structured['buttons']],
                ],
            ],
        ];
    }

    private function parseStructuredReply(string $rawReply): ?array
    {
        $decoded = $this->decodeJsonFromText($rawReply);
        if (!is_array($decoded)) {
            return null;
        }

        $text = trim((string) ($decoded['text'] ?? ''));
        $buttons = is_array($decoded['buttons'] ?? null) ? $decoded['buttons'] : [];

        if ($text === '' || empty($buttons)) {
            return null;
        }

        $normalizedButtons = [];
        foreach ($buttons as $button) {
            $title = trim((string) data_get($button, 'title', ''));
            if ($title === '') {
                continue;
            }

            $rawId = trim((string) data_get($button, 'id', ''));
            if ($rawId === '') {
                $rawId = Str::slug($title, '_');
            }

            $cleanId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $rawId);
            $cleanId = trim((string) $cleanId, '_');
            if ($cleanId === '') {
                $cleanId = 'btn_' . (string) (count($normalizedButtons) + 1);
            }

            $normalizedButtons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => Str::limit($cleanId, 128, ''),
                    'title' => Str::limit($title, 20, ''),
                ],
            ];
        }

        $normalizedButtons = array_values(array_slice($normalizedButtons, 0, 3));
        if (empty($normalizedButtons)) {
            return null;
        }

        return [
            'text' => Str::limit($text, 1024, ''),
            'buttons' => $normalizedButtons,
        ];
    }

    private function decodeJsonFromText(string $text): ?array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $trimmed, $m)) {
            $decoded = json_decode(trim((string) $m[1]), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        $firstBrace = strpos($trimmed, '{');
        $lastBrace = strrpos($trimmed, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $decoded = json_decode(substr($trimmed, $firstBrace, $lastBrace - $firstBrace + 1), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function supportsInteractiveButtons(Conversation $conversation): bool
    {
        return app(ConversationChannelManager::class)->supportsAiStructuredReply($conversation);
    }

    private function buildPromptHistory(Conversation $conversation): array
    {
        $history = ConversationMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->map(function (ConversationMessage $message) {
                return [
                    'role' => $message->direction === 'out' ? 'assistant' : 'user',
                    'content' => Str::limit((string) ($message->body ?? ''), self::HISTORY_ITEM_MAX_CHARS, '...'),
                ];
            })
            ->values()
            ->all();

        $total = ConversationMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->where('conversation_id', $conversation->id)
            ->count();

        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $rollingSummary = isset($metadata['rolling_summary']) ? (string) $metadata['rolling_summary'] : null;

        if ($total > self::HISTORY_LIMIT) {
            $olderLines = ConversationMessage::query()
                ->where('tenant_id', $this->tenantId())
                ->where('conversation_id', $conversation->id)
                ->orderByDesc('id')
                ->skip(self::HISTORY_LIMIT)
                ->limit(self::ROLLING_SUMMARY_LINES)
                ->get()
                ->reverse()
                ->map(function (ConversationMessage $message) {
                    $role = $message->direction === 'out' ? 'AI' : 'USER';
                    return $role . ': ' . Str::limit((string) ($message->body ?? ''), 140, '...');
                })
                ->all();

            if (!empty($olderLines)) {
                $rollingSummary = implode("\n", $olderLines);
                $metadata['rolling_summary'] = $rollingSummary;
                $conversation->update(['metadata' => $metadata]);
            }
        }

        return [$history, $rollingSummary];
    }

    private function acquireRequestLock(): bool
    {
        return Cache::add($this->requestLockKey(), 1, now()->addSeconds(self::REQUEST_LOCK_TTL_SECONDS));
    }

    private function requestLockKey(): string
    {
        return 'ai_reply_lock:' . $this->messageId;
    }

    private function responseCacheKey(int $accountId, array $payload): string
    {
        return 'ai_reply_cache:' . $accountId . ':' . sha1(json_encode($payload));
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }

    private function humanHandoffAcknowledgementMessage(): string
    {
        return 'Baik, percakapan ini kami teruskan ke tim kami agar dibantu lebih lanjut.';
    }
}
