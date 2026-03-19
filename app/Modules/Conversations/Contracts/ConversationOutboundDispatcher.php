<?php

namespace App\Modules\Conversations\Contracts;

use App\Modules\Conversations\Models\ConversationMessage;

interface ConversationOutboundDispatcher
{
    public function dispatch(ConversationMessage $message): bool;

    public function register(string $channel, callable $dispatcher): void;
}
