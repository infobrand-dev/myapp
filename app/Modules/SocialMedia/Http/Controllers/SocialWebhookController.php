<?php

namespace App\Modules\SocialMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Conversations\Contracts\InboxMessageIngester;
use App\Modules\Conversations\Data\InboxMessageEnvelope;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Chatbot\Services\ConversationBotManager;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\SocialMedia\Models\SocialAccountChatbotIntegration;
use App\Modules\Conversations\Jobs\GenerateAiReply;
use App\Modules\SocialMedia\Http\Requests\InboundSocialWebhookRequest;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SocialWebhookController extends Controller
{
    public function __construct(private readonly InboxMessageIngester $ingester)
    {
    }

    public function inbound(InboundSocialWebhookRequest $request): JsonResponse
    {
        $data = $request->validated();

        $token = trim((string) ($data['token'] ?? ''));
        $account = SocialAccount::query()
            ->where('status', 'active')
            ->where('platform', $data['platform'])
            ->when($data['account_id'] ?? null, fn ($q) => $q->where('id', $data['account_id']))
            ->where(function ($query) use ($token) {
                $query->where('access_token_hash', hash('sha256', $token))
                    ->orWhere('access_token', $token);
            })
            ->get()
            ->first(fn (SocialAccount $candidate) => hash_equals((string) $candidate->access_token, $token));

        if (!$account) {
            return response()->json(['message' => 'Invalid token/account'], Response::HTTP_UNAUTHORIZED);
        }

        TenantContext::setCurrentId((int) $account->tenant_id);

        $result = $this->ingester->ingest(new InboxMessageEnvelope(
            channel: 'social_dm',
            instanceId: (int) $account->id,
            conversationExternalId: null,
            contactExternalId: $data['contact_id'],
            contactName: $data['contact_name'] ?? null,
            direction: $data['direction'] ?? 'in',
            type: 'text',
            body: $data['message'],
            externalMessageId: $data['external_message_id'] ?? null,
            payload: $request->all(),
            conversationMetadata: ['platform' => $data['platform']],
            messageStatus: (($data['direction'] ?? 'in') === 'out') ? 'sent' : 'delivered',
            ingestionMode: InboxMessageEnvelope::MODE_REALTIME,
            incrementUnread: ($data['direction'] ?? 'in') !== 'out',
            writeActivityLog: false,
            broadcast: false,
        ));
        $conversation = $result->conversation;
        $message = $result->message;

        if ($result->deduplicated) {
            return response()->json(['stored' => true, 'deduplicated' => true]);
        }

        $chatbot = $this->chatbotIntegration($account);
        $shouldAutoReply = $chatbot['auto_reply']
            && (($data['direction'] ?? 'in') === 'in')
            && $this->shouldAutoReply($conversation, $chatbot['chatbot_account_id'], (string) ($message->body ?? ''));
        if ($shouldAutoReply) {
            GenerateAiReply::dispatch($conversation->id, $message->id, $chatbot['chatbot_account_id']);
        }

        return response()->json(['stored' => true, 'deduplicated' => $result->deduplicated]);
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
        app(ConversationBotManager::class)->pause($conversation, $reason);
    }
}
