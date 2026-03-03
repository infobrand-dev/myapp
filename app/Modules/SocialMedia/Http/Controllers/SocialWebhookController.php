<?php

namespace App\Modules\SocialMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\Conversations\Jobs\GenerateAiReply;
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
            'account_id' => ['nullable', 'integer'],
        ]);

        $account = SocialAccount::query()
            ->when($data['account_id'] ?? null, fn ($q) => $q->where('id', $data['account_id']))
            ->when(!($data['account_id'] ?? null), fn ($q) => $q->where('access_token', $data['token']))
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Invalid token/account'], Response::HTTP_UNAUTHORIZED);
        }

        $conversation = Conversation::firstOrCreate(
            [
                'channel' => 'social_dm',
                'instance_id' => $account->id,
                'contact_external_id' => $data['contact_id'],
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

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => $data['direction'] ?? 'in',
            'type' => 'text',
            'body' => $data['message'],
            'status' => 'delivered',
            'external_message_id' => $data['external_message_id'] ?? null,
            'payload' => $request->all(),
        ]);

        $shouldAutoReply = ($account->auto_reply ?? false) && $account->chatbot_account_id && (($data['direction'] ?? 'in') === 'in');
        if ($shouldAutoReply) {
            GenerateAiReply::dispatch($conversation->id, $message->id, $account->chatbot_account_id);
        }

        return response()->json(['stored' => true]);
    }

    // Meta challenge verify
    public function verify(Request $request): JsonResponse
    {
        $verifyToken = config('services.meta.verify_token', 'changeme');
        if ($request->get('hub_verify_token') === $verifyToken) {
            return response()->json($request->get('hub_challenge'));
        }
        return response()->json(['message' => 'Invalid verify token'], Response::HTTP_FORBIDDEN);
    }
}


