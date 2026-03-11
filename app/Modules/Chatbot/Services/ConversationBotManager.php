<?php

namespace App\Modules\Chatbot\Services;

use App\Modules\Conversations\Models\Conversation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConversationBotManager
{
    public function integrationForConversation(Conversation $conversation): ?array
    {
        $channel = strtolower((string) ($conversation->channel ?? ''));

        if ($channel === 'social_dm') {
            return $this->socialIntegration($conversation);
        }

        if ($channel === 'wa_api') {
            return $this->whatsAppIntegration($conversation);
        }

        return null;
    }

    public function hasConnectedBot(Conversation $conversation): bool
    {
        $integration = $this->integrationForConversation($conversation);

        return (bool) ($integration['connected'] ?? false);
    }

    public function pause(Conversation $conversation, string $reason = 'manual_pause'): void
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

    public function resume(Conversation $conversation): void
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

    private function socialIntegration(Conversation $conversation): ?array
    {
        if (
            !$conversation->instance_id
            || !class_exists(\App\Modules\SocialMedia\Models\SocialAccountChatbotIntegration::class)
            || !Schema::hasTable('social_account_chatbot_integrations')
        ) {
            return null;
        }

        $integration = DB::table('social_account_chatbot_integrations')
            ->where('social_account_id', (int) $conversation->instance_id)
            ->first(['auto_reply', 'chatbot_account_id']);

        if (!$integration || empty($integration->chatbot_account_id)) {
            return null;
        }

        return [
            'channel' => 'social_dm',
            'connected' => true,
            'auto_reply' => (bool) ($integration->auto_reply ?? false),
            'chatbot_account_id' => (int) $integration->chatbot_account_id,
        ];
    }

    private function whatsAppIntegration(Conversation $conversation): ?array
    {
        if (
            !$conversation->instance_id
            || !class_exists(\App\Modules\WhatsAppApi\Models\WhatsAppInstanceChatbotIntegration::class)
            || !Schema::hasTable('whatsapp_instance_chatbot_integrations')
        ) {
            return null;
        }

        $integration = DB::table('whatsapp_instance_chatbot_integrations')
            ->where('instance_id', (int) $conversation->instance_id)
            ->first(['auto_reply', 'chatbot_account_id']);

        if (!$integration || empty($integration->chatbot_account_id)) {
            return null;
        }

        return [
            'channel' => 'wa_api',
            'connected' => true,
            'auto_reply' => (bool) ($integration->auto_reply ?? false),
            'chatbot_account_id' => (int) $integration->chatbot_account_id,
        ];
    }
}
