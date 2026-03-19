<?php

namespace App\Modules\WhatsAppWeb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppWeb\Services\WhatsAppWebBridgeClient;
use App\Modules\WhatsAppWeb\Services\WhatsAppWebConversationSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ChatController extends Controller
{
    public function send(Request $request, string $chatId, WhatsAppWebBridgeClient $bridge, WhatsAppWebConversationSyncService $sync): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string'],
            'client_id' => ['nullable', 'string', 'max:100'],
        ]);

        $clientId = trim((string) ($data['client_id'] ?? (string) $request->user()->id));

        try {
            $result = $bridge->sendMessage($chatId, $data['message'], $clientId);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $stored = $sync->syncMessage([
            'client_id' => $clientId,
            'contact_id' => $chatId,
            'contact_name' => $result['author'] ?: $chatId,
            'message' => $result['body'],
            'external_message_id' => $result['id'],
            'direction' => 'out',
            'type' => $result['type'],
        ], (int) $request->user()->id);

        return response()->json([
            'ok' => true,
            'message' => [
                'id' => $stored->id,
                'external_message_id' => $stored->external_message_id,
                'status' => $stored->status,
                'body' => $stored->body,
            ],
        ]);
    }
}
