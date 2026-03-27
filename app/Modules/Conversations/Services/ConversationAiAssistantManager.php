<?php

namespace App\Modules\Conversations\Services;

use App\Modules\Conversations\Contracts\ConversationAiAssistantRegistry;

class ConversationAiAssistantManager implements ConversationAiAssistantRegistry
{
    /**
     * @var array<int, callable>
     */
    private array $accountResolvers = [];

    public function registerAccountResolver(callable $resolver): void
    {
        $this->accountResolvers[] = $resolver;
    }

    public function resolveAccount(?int $accountId): mixed
    {
        foreach ($this->accountResolvers as $resolver) {
            $account = $resolver($accountId);
            if ($account !== null) {
                return $account;
            }
        }

        return null;
    }
}
