<?php

namespace App\Modules\Conversations\Jobs;

use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use Illuminate\Support\Facades\Http;
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

    public int $conversationId;
    public int $messageId;
    public ?int $chatbotAccountId;

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

        $history = ConversationMessage::where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->reverse()
            ->map(function ($msg) {
                $role = $msg->direction === 'out' ? 'assistant' : 'user';
                return ['role' => $role, 'content' => $msg->body ?? ''];
            })
            ->values()
            ->all();

        $payload = [
            'model' => $aiAccount->model ?: config('services.openai.model', 'gpt-4o-mini'),
            'messages' => array_merge([
                ['role' => 'system', 'content' => $this->systemPrompt($conversation->channel)],
            ], $history),
            'max_tokens' => 200,
            'temperature' => 0.5,
        ];

        try {
            $response = Http::withToken($aiAccount->api_key)
                ->timeout(10)
                ->post('https://api.openai.com/v1/chat/completions', $payload);
            $reply = $response->successful()
                ? ($response->json('choices.0.message.content') ?? null)
                : null;
        } catch (\Throwable $e) {
            Log::error('AI request failed', ['error' => $e->getMessage()]);
            $reply = null;
        }

        if (!$reply) {
            $reply = "Terima kasih, pesan Anda sudah kami terima.";
        }

        $outgoing = $this->buildOutgoingReply((string) $reply, $conversation->channel);

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

    private function systemPrompt(string $channel): string
    {
        $base = 'Kamu adalah asisten CS singkat dan sopan berbahasa Indonesia.';

        if ($channel !== 'wa_api') {
            return $base;
        }

        return $base . ' Jika relevan, kamu boleh menambahkan tombol cepat WhatsApp dengan format JSON berikut TANPA teks lain: '
            . '{"text":"teks balasan","buttons":[{"id":"id_opsi_1","title":"Label 1"},{"id":"id_opsi_2","title":"Label 2"}]}. '
            . 'Gunakan max 3 tombol. Jika tidak perlu tombol, jawab teks biasa.';
    }

    private function buildOutgoingReply(string $rawReply, string $channel): array
    {
        $fallbackText = trim($rawReply) !== '' ? trim($rawReply) : 'Terima kasih, pesan Anda sudah kami terima.';

        if ($channel !== 'wa_api') {
            return [
                'type' => 'text',
                'body' => $fallbackText,
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

        return null;
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
}
