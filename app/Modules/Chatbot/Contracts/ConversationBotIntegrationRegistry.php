<?php

namespace App\Modules\Chatbot\Contracts;

use App\Modules\Conversations\Models\Conversation;

interface ConversationBotIntegrationRegistry
{
    public function register(string $channel, callable $resolver): void;

    public function resolve(Conversation $conversation): ?array;
}
