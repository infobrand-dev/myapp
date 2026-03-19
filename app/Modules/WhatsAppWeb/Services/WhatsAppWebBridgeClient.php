<?php

namespace App\Modules\WhatsAppWeb\Services;

use App\Modules\WhatsAppWeb\Support\RuntimeSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppWebBridgeClient
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getChats(?string $clientId = null, int $limit = 50, bool $activeOnly = false): array
    {
        try {
            $response = Http::baseUrl(rtrim(RuntimeSettings::waWebBridgeUrl(), '/'))
                ->acceptJson()
                ->timeout(30)
                ->get('/chats', [
                    'clientId' => $this->clientId($clientId),
                    'limit' => max(1, min(200, $limit)),
                    'activeOnly' => $activeOnly ? 1 : 0,
                ])
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('Bridge WhatsApp Web tidak dapat mengambil daftar chat.', 0, $e);
        }

        $payload = $response->json();

        return is_array($payload) ? array_values(array_filter($payload, 'is_array')) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMessages(string $chatId, int $limit = 100, ?string $clientId = null): array
    {
        try {
            $response = Http::baseUrl(rtrim(RuntimeSettings::waWebBridgeUrl(), '/'))
                ->acceptJson()
                ->timeout(60)
                ->get('/chats/' . rawurlencode($chatId) . '/messages', [
                    'clientId' => $this->clientId($clientId),
                    'limit' => max(1, min(500, $limit)),
                ])
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('Bridge WhatsApp Web tidak dapat mengambil histori chat.', 0, $e);
        }

        $payload = $response->json();

        return is_array($payload) ? array_values(array_filter($payload, 'is_array')) : [];
    }

    /**
     * @return array{id: string|null, body: string, type: string, from: string, author: string|null, fromMe: bool}
     */
    public function sendMessage(string $chatId, string $message, ?string $clientId = null): array
    {
        try {
            $response = Http::baseUrl(rtrim(RuntimeSettings::waWebBridgeUrl(), '/'))
                ->acceptJson()
                ->timeout(15)
                ->post(
                    '/chats/' . rawurlencode($chatId) . '/messages?' . http_build_query(['clientId' => $this->clientId($clientId)]),
                    ['message' => $message]
                )
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('Bridge WhatsApp Web tidak dapat dijangkau.', 0, $e);
        }

        $payload = $response->json();

        if (!is_array($payload) || !($payload['ok'] ?? false)) {
            throw new RuntimeException('Bridge WhatsApp Web gagal mengirim pesan.');
        }

        return [
            'id' => is_string(data_get($payload, 'message.id')) ? data_get($payload, 'message.id') : null,
            'body' => (string) data_get($payload, 'message.body', $message),
            'type' => (string) data_get($payload, 'message.type', 'text'),
            'from' => (string) data_get($payload, 'message.from', $chatId),
            'author' => data_get($payload, 'message.author'),
            'fromMe' => (bool) data_get($payload, 'message.fromMe', true),
        ];
    }

    private function clientId(?string $clientId): string
    {
        $resolved = trim((string) $clientId);

        return $resolved !== '' ? $resolved : 'default';
    }
}
