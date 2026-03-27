<?php

namespace App\Modules\Conversations\Contracts;

interface ConversationAiAssistantRegistry
{
    public function registerAccountResolver(callable $resolver): void;

    public function resolveAccount(?int $accountId): mixed;
}
