<?php

namespace App\Modules\Chatbot\Services;

use App\Modules\Chatbot\Contracts\ConversationBotIntegrationRegistry;
use App\Modules\Conversations\Models\Conversation;
use Illuminate\Support\Arr;

class ConversationBotManager
{
    public function __construct(
        private readonly ConversationBotIntegrationRegistry $integrations
    ) {
    }

    public function integrationForConversation(Conversation $conversation): ?array
    {
        return $this->integrations->resolve($conversation);
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
}
