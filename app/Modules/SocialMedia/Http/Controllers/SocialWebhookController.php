<?php

namespace App\Modules\SocialMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SocialWebhookController extends Controller
{
    public function inbound(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['required', 'in:instagram,facebook'],
            'contact_id' => ['required', 'string'],
            'contact_name' => ['nullable', 'string'],
            'message' => ['required', 'string'],
            'external_message_id' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:in,out'],
            'instance_key' => ['nullable', 'string'],
        ]);

        $instance = WhatsAppInstance::where('api_token', $data['token'])
            ->when($data['instance_key'] ?? null, fn ($q) => $q->where('id', $data['instance_key']))
            ->first();

        if (!$instance) {
            return response()->json(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }

        $conversation = Conversation::firstOrCreate(
            [
                'channel' => 'social_dm',
                'instance_id' => $instance->id,
                'contact_wa_id' => $data['contact_id'],
            ],
            [
                'contact_name' => $data['contact_name'] ?? null,
                'status' => 'open',
                'last_message_at' => now(),
                'last_incoming_at' => now(),
                'unread_count' => 1,
                'metadata' => ['platform' => $data['platform']],
            ]
        );

        $conversation->update([
            'contact_name' => $data['contact_name'] ?? $conversation->contact_name,
            'last_message_at' => now(),
            'last_incoming_at' => now(),
            'unread_count' => ($conversation->unread_count ?? 0) + 1,
            'metadata' => array_merge($conversation->metadata ?? [], ['platform' => $data['platform']]),
        ]);

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => $data['direction'] ?? 'in',
            'type' => 'text',
            'body' => $data['message'],
            'status' => 'delivered',
            'wa_message_id' => $data['external_message_id'] ?? null,
            'payload' => $request->all(),
        ]);

        return response()->json(['stored' => true]);
    }
}
