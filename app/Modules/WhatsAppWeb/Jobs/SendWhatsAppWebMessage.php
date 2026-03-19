<?php

namespace App\Modules\WhatsAppWeb\Jobs;

use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppWeb\Services\WhatsAppWebBridgeClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SendWhatsAppWebMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $messageId)
    {
    }

    public function handle(WhatsAppWebBridgeClient $bridge): void
    {
        $message = ConversationMessage::query()
            ->with('conversation')
            ->find($this->messageId);

        if (!$message || !$message->conversation || $message->conversation->channel !== 'wa_web') {
            return;
        }

        $conversation = $message->conversation;
        $chatId = trim((string) ($conversation->contact_external_id ?? ''));
        $clientId = trim((string) data_get($conversation->metadata, 'client_id', 'default'));
        $body = trim((string) ($message->body ?? ''));

        if ($chatId === '' || $body === '') {
            $message->update([
                'status' => 'failed',
                'error_message' => 'Chat ID atau body WhatsApp Web tidak valid.',
            ]);
            return;
        }

        try {
            $result = $bridge->sendMessage($chatId, $body, $clientId);
        } catch (RuntimeException $e) {
            $message->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return;
        }

        $payload = is_array($message->payload) ? $message->payload : [];
        $payload['bridge_result'] = $result;

        $message->update([
            'status' => 'sent',
            'sent_at' => now(),
            'external_message_id' => $result['id'] ?: $message->external_message_id,
            'payload' => $payload,
            'error_message' => null,
        ]);
    }
}
