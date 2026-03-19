<?php

namespace App\Modules\WhatsAppWeb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppWeb\Services\WhatsAppWebBridgeClient;
use App\Modules\WhatsAppWeb\Services\WhatsAppWebConversationSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ConversationSyncController extends Controller
{
    public function syncChat(
        Request $request,
        string $chatId,
        WhatsAppWebBridgeClient $bridge,
        WhatsAppWebConversationSyncService $sync
    ): JsonResponse {
        $data = $request->validate([
            'client_id' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $clientId = trim((string) ($data['client_id'] ?? (string) $request->user()->id));
        $limit = (int) ($data['limit'] ?? 150);

        try {
            $chat = collect($bridge->getChats($clientId, 200, false))
                ->firstWhere('id', $chatId) ?? ['id' => $chatId, 'name' => $chatId, 'client_id' => $clientId];
            $chat['client_id'] = $clientId;
            $messages = $bridge->getMessages($chatId, $limit, $clientId);
            $result = $sync->syncHistory($chat, $messages, (int) $request->user()->id);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Histori chat berhasil disinkronkan.',
            'result' => $result,
        ]);
    }

    public function syncActiveChats(
        Request $request,
        WhatsAppWebBridgeClient $bridge,
        WhatsAppWebConversationSyncService $sync
    ): JsonResponse {
        $data = $request->validate([
            'client_id' => ['nullable', 'string', 'max:100'],
            'chat_limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'message_limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $clientId = trim((string) ($data['client_id'] ?? (string) $request->user()->id));
        $chatLimit = (int) ($data['chat_limit'] ?? 50);
        $messageLimit = (int) ($data['message_limit'] ?? 100);

        try {
            $chats = collect($bridge->getChats($clientId, $chatLimit, true))
                ->map(function (array $chat) use ($clientId) {
                    $chat['client_id'] = $clientId;

                    return $chat;
                })
                ->values();

            $results = [];
            $totalImported = 0;
            $totalDeduplicated = 0;

            foreach ($chats as $chat) {
                $history = $bridge->getMessages((string) $chat['id'], $messageLimit, $clientId);
                $result = $sync->syncHistory($chat, $history, (int) $request->user()->id);
                $results[] = $result;
                $totalImported += (int) ($result['imported_count'] ?? 0);
                $totalDeduplicated += (int) ($result['deduplicated_count'] ?? 0);
            }
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Histori chat aktif berhasil disinkronkan.',
            'summary' => [
                'chat_count' => $chats->count(),
                'imported_count' => $totalImported,
                'deduplicated_count' => $totalDeduplicated,
            ],
            'results' => $results,
        ]);
    }
}
