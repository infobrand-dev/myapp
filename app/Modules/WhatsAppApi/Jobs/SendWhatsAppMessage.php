<?php

namespace App\Modules\WhatsAppApi\Jobs;

use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $messageId;

    public function __construct(int $messageId)
    {
        $this->messageId = $messageId;
    }

    public function handle(): void
    {
        $message = ConversationMessage::with('conversation')->find($this->messageId);
        if (!$message || !$message->conversation || $message->conversation->channel !== 'wa_api') {
            return;
        }

        $conversation = $message->conversation;
        $instance = WhatsAppInstance::find($conversation->instance_id);
        if (!$instance || !$instance->api_base_url || !$instance->api_token) {
            $message->update(['status' => 'error', 'error_message' => 'Instance not configured']);
            return;
        }

        $endpoint = rtrim($instance->api_base_url, '/') . '/messages/send';

        try {
            $response = Http::withToken($instance->api_token)
                ->post($endpoint, [
                    'to' => $conversation->contact_wa_id,
                    'type' => 'text',
                    'message' => $message->body,
                    'reference_id' => $message->id,
                ]);

            if ($response->successful()) {
                $message->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'wa_message_id' => $response->json('message_id') ?? $message->wa_message_id,
                ]);
            } else {
                $message->update([
                    'status' => 'error',
                    'error_message' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed send WA message', ['message_id' => $message->id, 'error' => $e->getMessage()]);
            $message->update(['status' => 'error', 'error_message' => $e->getMessage()]);
        }
    }
}
