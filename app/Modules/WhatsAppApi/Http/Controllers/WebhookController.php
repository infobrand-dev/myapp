<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Jobs\GenerateAiReply;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
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
                'channel' => 'wa_api',
                'instance_id' => $instance->id,
                'contact_wa_id' => $data['contact_id'],
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

        $msg = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => $data['direction'] ?? 'in',
            'type' => 'text',
            'body' => $data['message'],
            'status' => 'delivered',
            'wa_message_id' => $data['external_message_id'] ?? null,
            'payload' => $request->all(),
        ]);

        $shouldAutoReply = ($instance->auto_reply ?? false) && $instance->chatbot_account_id && (($data['direction'] ?? 'in') === 'in');
        if ($shouldAutoReply) {
            GenerateAiReply::dispatch($conversation->id, $msg->id, $instance->chatbot_account_id);
        }

        return response()->json(['stored' => true]);
    }
}
