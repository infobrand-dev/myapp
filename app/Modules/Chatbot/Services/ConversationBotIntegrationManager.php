<?php

namespace App\Modules\Chatbot\Services;

use App\Modules\Chatbot\Contracts\ConversationBotIntegrationRegistry;
use App\Modules\Conversations\Models\Conversation;

class ConversationBotIntegrationManager implements ConversationBotIntegrationRegistry
{
    /**
     * @var array<string, callable>
     */
    private array $resolvers = [];

    public function register(string $channel, callable $resolver): void
    {
        $this->resolvers[$channel] = $resolver;
    }

    public function resolve(Conversation $conversation): ?array
    {
        $channel = strtolower((string) ($conversation->channel ?? ''));
        $resolver = $this->resolvers[$channel] ?? null;

        if (!$resolver) {
            return null;
        }

        $integration = $resolver($conversation);

        return is_array($integration) ? $integration : null;
    }
}
