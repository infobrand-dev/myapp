<?php

namespace App\Modules\SocialMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\SocialMedia\Models\SocialAccountChatbotIntegration;
use App\Modules\Conversations\Jobs\GenerateAiReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
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
            ->where('status', 'active')
            ->where('platform', $data['platform'])
            ->when($data['account_id'] ?? null, fn ($q) => $q->where('id', $data['account_id']))
            ->where('access_token', $data['token'])
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

        if (!empty($data['external_message_id'])) {
            $alreadyStored = ConversationMessage::query()
                ->where('conversation_id', $conversation->id)
                ->where('external_message_id', $data['external_message_id'])
                ->exists();

            if ($alreadyStored) {
                return response()->json(['stored' => true, 'deduplicated' => true]);
            }
        }

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

        $chatbot = $this->chatbotIntegration($account);
        $shouldAutoReply = $chatbot['auto_reply']
            && (($data['direction'] ?? 'in') === 'in')
            && $this->shouldAutoReply($conversation, $chatbot['chatbot_account_id'], (string) ($message->body ?? ''));
        if ($shouldAutoReply) {
            GenerateAiReply::dispatch($conversation->id, $message->id, $chatbot['chatbot_account_id']);
        }

        return response()->json(['stored' => true]);
    }

    // Meta challenge verify
    public function verify(Request $request)
    {
        $verifyToken = config('services.meta.verify_token', 'changeme');
        if ($request->get('hub_verify_token') === $verifyToken) {
            return response((string) $request->get('hub_challenge'), Response::HTTP_OK)
                ->header('Content-Type', 'text/plain');
        }
        return response()->json(['message' => 'Invalid verify token'], Response::HTTP_FORBIDDEN);
    }

    private function chatbotIntegration(SocialAccount $account): array
    {
        $fallback = [
            'auto_reply' => (bool) ($account->auto_reply ?? false),
            'chatbot_account_id' => $account->chatbot_account_id ? (int) $account->chatbot_account_id : null,
        ];

        if (!Schema::hasTable('social_account_chatbot_integrations')) {
            return $fallback;
        }

        /** @var SocialAccountChatbotIntegration|null $integration */
        $integration = $account->chatbotIntegration()->first();
        if (!$integration) {
            return $fallback;
        }

        return [
            'auto_reply' => (bool) $integration->auto_reply,
            'chatbot_account_id' => $integration->chatbot_account_id ? (int) $integration->chatbot_account_id : null,
        ];
    }

    private function shouldAutoReply(Conversation $conversation, ?int $chatbotAccountId, string $incomingBody): bool
    {
        if (!$chatbotAccountId) {
            return false;
        }

        $account = $this->resolveChatbotAccount($chatbotAccountId);
        if (!$account) {
            return false;
        }

        $mode = strtolower((string) ($account->operation_mode ?? 'ai_only'));
        if ($mode === 'ai_only') {
            return true;
        }

        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        if ((bool) ($metadata['auto_reply_paused'] ?? false)) {
            return false;
        }

        if ($this->shouldHandoffToHuman($incomingBody)) {
            $this->markConversationHandoff($conversation, 'keyword_request_human');
            return false;
        }

        return true;
    }

    private function resolveChatbotAccount(int $chatbotAccountId)
    {
        $chatbotClass = \App\Modules\Chatbot\Models\ChatbotAccount::class;
        if (!class_exists($chatbotClass) || !Schema::hasTable('chatbot_accounts')) {
            return null;
        }

        return $chatbotClass::query()
            ->where('status', 'active')
            ->find($chatbotAccountId);
    }

    private function shouldHandoffToHuman(string $text): bool
    {
        $haystack = mb_strtolower(trim($text));
        if ($haystack === '') {
            return false;
        }

        $keywords = [
            'agent',
            'admin',
            'operator',
            'manusia',
            'human',
            'cs',
            'customer service',
            'staff',
        ];

        foreach ($keywords as $keyword) {
            if (mb_stripos($haystack, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function markConversationHandoff(Conversation $conversation, string $reason): void
    {
        self::pauseBotForConversation($conversation, $reason);
    }

    public static function pauseBotForConversation(Conversation $conversation, string $reason = 'manual_pause'): void
    {
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        $metadata['needs_human'] = true;
        $metadata['auto_reply_paused'] = true;
        $metadata['handoff_reason'] = $reason;
        $metadata['handoff_at'] = now()->toDateTimeString();

        $conversation->update([
            'metadata' => $metadata,
        ]);
    }

    public static function resumeBotForConversation(Conversation $conversation): void
    {
        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        Arr::set($metadata, 'needs_human', false);
        Arr::set($metadata, 'auto_reply_paused', false);
        Arr::set($metadata, 'handoff_resumed_at', now()->toDateTimeString());
        Arr::forget($metadata, ['handoff_reason', 'handoff_at']);

        $conversation->update([
            'metadata' => $metadata,
        ]);
    }
}


