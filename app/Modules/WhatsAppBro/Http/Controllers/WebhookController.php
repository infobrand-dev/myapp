<?php

namespace App\Modules\WhatsAppBro\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Support\ModuleRuntimeSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function inbound(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'contact_id' => ['required', 'string'],
            'contact_name' => ['nullable', 'string'],
            'message' => ['required', 'string'],
            'external_message_id' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:in,out'],
        ]);

        $expected = ModuleRuntimeSettings::waBroWebhookToken();
        if ($expected && $expected !== $data['token']) {
            return response()->json(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }

        $conversation = Conversation::firstOrCreate(
            [
                'channel' => 'wa_bro',
                'instance_id' => null,
                'contact_external_id' => $data['contact_id'],
            ],
            [
                'contact_name' => $data['contact_name'] ?? null,
                'status' => 'open',
                'last_message_at' => now(),
                'last_incoming_at' => now(),
                'unread_count' => 1,
            ]
        );

        $conversation->update([
            'contact_name' => $data['contact_name'] ?? $conversation->contact_name,
            'last_message_at' => now(),
            'last_incoming_at' => now(),
            'unread_count' => ($conversation->unread_count ?? 0) + 1,
        ]);

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => $data['direction'] ?? 'in',
            'type' => 'text',
            'body' => $data['message'],
            'status' => 'delivered',
            'external_message_id' => $data['external_message_id'] ?? null,
            'payload' => $request->all(),
        ]);

        return response()->json(['stored' => true]);
    }
}


