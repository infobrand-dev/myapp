<?php

namespace App\Modules\Conversations\Jobs;

use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
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
        $conversation = Conversation::find($this->conversationId);
        $incoming = ConversationMessage::find($this->messageId);
        if (!$conversation || !$incoming || $incoming->direction !== 'in') return;

        if (!$this->acquireRequestLock()) {
            Log::info('AI reply skipped: duplicate in-flight request', [
                'conversation_id' => $this->conversationId,
                'message_id' => $this->messageId,
            ]);
            return;
        }

        $chatbotClass = \App\Modules\Chatbot\Models\ChatbotAccount::class;
        if (!class_exists($chatbotClass)) {
            Log::warning('AI reply skipped: chatbot module not ready', ['conversation_id' => $this->conversationId]);
            return;
        }

        $aiAccount = $this->chatbotAccountId
            ? $chatbotClass::where('status', 'active')->find($this->chatbotAccountId)
            : null;
        if (!$aiAccount || !$aiAccount->api_key) {
            Log::warning('AI reply skipped: no active chatbot account', ['conversation_id' => $this->conversationId]);
            return;
        }

        if ($this->shouldPauseForHuman($conversation, $aiAccount)) {
            Log::info('AI reply skipped: conversation paused for human', [
                'conversation_id' => $this->conversationId,
                'chatbot_account_id' => $aiAccount->id,
            ]);
            return;
        }

        [$history, $rollingSummary] = $this->buildPromptHistory($conversation);

        $systemMessages = [
            ['role' => 'system', 'content' => $this->systemPrompt($conversation)],
        ];
        if ($rollingSummary !== null && trim($rollingSummary) !== '') {
            $systemMessages[] = [
                'role' => 'system',
                'content' => "Ringkasan konteks sebelumnya:\n{$rollingSummary}",
            ];
        }

        $payload = [
            'model' => $aiAccount->model ?: config('services.openai.model', 'gpt-4o-mini'),
            'messages' => array_merge($systemMessages, $history),
            'max_tokens' => 200,
            'temperature' => 0.5,
        ];

        $cacheKey = $this->responseCacheKey((int) $aiAccount->id, $payload);
        try {
            $reply = Cache::get($cacheKey);

            if ($reply === null) {
                $response = Http::withToken($aiAccount->api_key)
                    ->timeout(10)
                    ->post('https://api.openai.com/v1/chat/completions', $payload);
                $reply = $response->successful()
                    ? ($response->json('choices.0.message.content') ?? null)
                    : null;

                if (is_string($reply) && trim($reply) !== '') {
                    Cache::put($cacheKey, $reply, now()->addSeconds(self::RESPONSE_CACHE_TTL_SECONDS));
                }
            }
        } catch (\Throwable $e) {
            Log::error('AI request failed', ['error' => $e->getMessage()]);
            $reply = null;
        }

        if (!$reply) {
            $reply = "Terima kasih, pesan Anda sudah kami terima.";
        }

        $outgoing = $this->buildOutgoingReply((string) $reply, $conversation);

        $replyMessage = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'direction' => 'out',
            'type' => $outgoing['type'],
            'body' => $outgoing['body'],
            'payload' => $outgoing['payload'],
            'status' => $conversation->channel === 'wa_api' ? 'queued' : 'sent',
            'sent_at' => $conversation->channel === 'wa_api' ? null : now(),
        ]);

        if ($conversation->channel === 'wa_api') {
            $waJobClass = \App\Modules\WhatsAppApi\Jobs\SendWhatsAppMessage::class;
            if (class_exists($waJobClass)) {
                $waJobClass::dispatch($replyMessage->id);
            }
        } elseif ($conversation->channel === 'social_dm') {
            $socialJobClass = \App\Modules\SocialMedia\Jobs\SendSocialMessage::class;
            if (class_exists($socialJobClass)) {
                $socialJobClass::dispatch($replyMessage->id);
            }
        }
    }

    private function systemPrompt(Conversation $conversation): string
    {
        $base = 'Kamu adalah asisten CS singkat dan sopan berbahasa Indonesia.';

        if ($conversation->channel !== 'wa_api' || !$this->supportsInteractiveButtons($conversation)) {
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
        if ($conversation->channel !== 'wa_api' || !$conversation->instance_id) {
            return false;
        }

        $instance = WhatsAppInstance::query()->find($conversation->instance_id);
        if (!$instance) {
            return false;
        }

        return strtolower((string) ($instance->provider ?? '')) === 'cloud';
    }

    private function shouldPauseForHuman(Conversation $conversation, $aiAccount): bool
    {
        $mode = strtolower((string) ($aiAccount->operation_mode ?? 'ai_only'));
        if ($mode !== 'ai_then_human') {
            return false;
        }

        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        return (bool) ($metadata['auto_reply_paused'] ?? false);
    }

    private function buildPromptHistory(Conversation $conversation): array
    {
        $history = ConversationMessage::query()
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
            ->where('conversation_id', $conversation->id)
            ->count();

        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $rollingSummary = isset($metadata['rolling_summary']) ? (string) $metadata['rolling_summary'] : null;

        if ($total > self::HISTORY_LIMIT) {
            $olderLines = ConversationMessage::query()
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
}
