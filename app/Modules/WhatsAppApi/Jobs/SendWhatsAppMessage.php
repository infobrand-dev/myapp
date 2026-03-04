<?php

namespace App\Modules\WhatsAppApi\Jobs;

use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 30;
    public int $maxExceptions = 3;

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

        if (in_array((string) $message->status, ['sent', 'delivered', 'read'], true)) {
            return;
        }

        $conversation = $message->conversation;
        $instance = WhatsAppInstance::find($conversation->instance_id);
        if (!$instance) {
            $message->update(['status' => 'error', 'error_message' => 'Instance not found']);
            return;
        }

        // Cloud API path
        $provider = strtolower($instance->provider ?? '');
        if (
            $provider === 'cloud'
            && $instance->phone_number_id
            && $instance->cloud_token
        ) {
            $this->sendViaCloud($message, $conversation->contact_external_id, $instance);
            return;
        }

        if (!$instance->api_base_url || !$instance->api_token) {
            $message->update(['status' => 'error', 'error_message' => 'Instance not configured']);
            return;
        }

        $endpoint = rtrim($instance->api_base_url, '/') . '/messages/send';

        $payload = [
            'to' => $conversation->contact_external_id,
            'reference_id' => $message->id,
        ];

        if (in_array($message->type, ['image', 'video', 'document', 'audio'], true)) {
            $payload['type'] = $message->type;
            $payload['media_url'] = $message->media_url ?: data_get($message->payload, 'link');
            $payload['message'] = $message->body;
            $payload['payload'] = $message->payload;
        } else {
            $payload['type'] = 'text';
            $payload['message'] = $message->body;
        }

        try {
            $response = Http::withToken($instance->api_token)
                ->timeout(20)
                ->post($endpoint, $payload);

            $this->processGatewayResponse($message, $response);
        } catch (Throwable $e) {
            Log::error('Failed send WA message', ['message_id' => $message->id, 'error' => $e->getMessage()]);
            $this->markRetryableFailure($message, $e->getMessage());
        }
    }

    public function backoff(): array
    {
        return [10, 30, 120, 300];
    }

    public function failed(Throwable $e): void
    {
        $message = ConversationMessage::find($this->messageId);
        if (!$message || in_array((string) $message->status, ['sent', 'delivered', 'read'], true)) {
            return;
        }

        $message->update([
            'status' => 'error',
            'error_message' => mb_substr(
                ($message->error_message ? $message->error_message . ' | ' : '') . 'Queue failed: ' . $e->getMessage(),
                0,
                1000
            ),
        ]);
    }

    private function sendViaCloud(ConversationMessage $message, string $to, WhatsAppInstance $instance): void
    {
        $base = rtrim(config('services.wa_cloud.base_url', 'https://graph.facebook.com/v22.0'), '/');
        $phoneId = $instance->phone_number_id;
        $token = $instance->cloud_token;

        $url = "{$base}/{$phoneId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
        ];

        if ($message->type === 'template' && is_array($message->payload)) {
            $tpl = $message->payload;
            $payload['type'] = 'template';
            $payload['template'] = [
                'name' => $tpl['name'],
                'language' => ['code' => $tpl['language']],
                'components' => $tpl['components'] ?? [],
            ];
        } elseif (in_array($message->type, ['image', 'video', 'document', 'audio', 'sticker'], true)) {
            $type = $message->type;
            $link = data_get($message->payload, 'link') ?: $message->media_url;
            $id = data_get($message->payload, 'id');

            $payload['type'] = $type;
            $payload[$type] = array_filter([
                'link' => $link,
                'id' => $id,
                'caption' => in_array($type, ['image', 'video', 'document'], true) ? $message->body : null,
                'filename' => $type === 'document' ? data_get($message->payload, 'filename') : null,
            ], fn ($value) => $value !== null && $value !== '');
        } elseif ($message->type === 'location') {
            $payload['type'] = 'location';
            $payload['location'] = [
                'latitude' => (float) data_get($message->payload, 'latitude'),
                'longitude' => (float) data_get($message->payload, 'longitude'),
                'name' => data_get($message->payload, 'name'),
                'address' => data_get($message->payload, 'address'),
            ];
        } else {
            $payload['type'] = 'text';
            $payload['text'] = [
                'preview_url' => false,
                'body' => $message->body,
            ];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(20)
                ->post($url, $payload);

            $this->processCloudResponse($message, $response);
        } catch (Throwable $e) {
            Log::error('WA Cloud send failed', ['id' => $message->id, 'error' => $e->getMessage()]);
            $this->markRetryableFailure($message, $e->getMessage());
        }
    }

    private function processGatewayResponse(ConversationMessage $message, Response $response): void
    {
        if ($response->successful()) {
            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
                'external_message_id' => $response->json('message_id') ?? $message->external_message_id,
                'error_message' => null,
            ]);
            return;
        }

        $this->handleHttpFailure($message, $response, 'Gateway');
    }

    private function processCloudResponse(ConversationMessage $message, Response $response): void
    {
        if ($response->successful()) {
            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
                'external_message_id' => $response->json('messages.0.id') ?? $message->external_message_id,
                'error_message' => null,
            ]);
            return;
        }

        $this->handleHttpFailure($message, $response, 'Cloud API');
    }

    private function handleHttpFailure(ConversationMessage $message, Response $response, string $source): void
    {
        $statusCode = $response->status();
        $body = trim((string) $response->body());
        $errorMessage = "{$source} {$statusCode}: " . ($body !== '' ? $body : 'Unknown error');
        $shortError = mb_substr($errorMessage, 0, 1000);

        $message->update([
            'status' => 'error',
            'error_message' => $shortError,
        ]);

        if ($statusCode === 429 || $statusCode >= 500) {
            throw new RuntimeException($shortError);
        }
    }

    private function markRetryableFailure(ConversationMessage $message, string $error): void
    {
        $shortError = mb_substr($error, 0, 1000);

        $message->update([
            'status' => 'error',
            'error_message' => $shortError,
        ]);

        throw new RuntimeException($shortError);
    }
}


