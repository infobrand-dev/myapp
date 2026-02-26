<?php

namespace App\Modules\Conversations\Jobs;

use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\WhatsAppApi\Jobs\SendWhatsAppMessage;
use App\Modules\SocialMedia\Jobs\SendSocialMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        $aiAccount = $this->chatbotAccountId
            ? ChatbotAccount::where('status', 'active')->find($this->chatbotAccountId)
            : null;
        if (!$aiAccount || !$aiAccount->api_key) {
            Log::warning('AI reply skipped: no active chatbot account', ['conversation_id' => $this->conversationId]);
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
                ['role' => 'system', 'content' => 'Kamu adalah asisten CS singkat dan sopan berbahasa Indonesia.'],
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

        $replyMessage = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'direction' => 'out',
            'type' => 'text',
            'body' => $reply,
            'status' => $conversation->channel === 'wa_api' ? 'queued' : 'sent',
            'sent_at' => $conversation->channel === 'wa_api' ? null : now(),
        ]);

        if ($conversation->channel === 'wa_api') {
            SendWhatsAppMessage::dispatch($replyMessage->id);
        } elseif ($conversation->channel === 'social_dm') {
            SendSocialMessage::dispatch($replyMessage->id);
        }
    }
}
