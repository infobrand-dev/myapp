<?php

namespace App\Modules\Conversations\Services;

use App\Modules\Conversations\Contracts\ConversationOutboundDispatcher;
use App\Modules\Conversations\Models\ConversationMessage;

class ConversationOutboundRegistry implements ConversationOutboundDispatcher
{
    /**
     * @var array<string, callable>
     */
    private array $dispatchers = [];

    public function dispatch(ConversationMessage $message): bool
    {
        $channel = (string) optional($message->conversation)->channel;
        if ($channel === '' || !isset($this->dispatchers[$channel])) {
            return false;
        }

        $dispatcher = $this->dispatchers[$channel];
        $dispatcher($message);

        return true;
    }

    public function register(string $channel, callable $dispatcher): void
    {
        $this->dispatchers[$channel] = $dispatcher;
    }
}
