<?php

namespace App\Modules\Conversations\Data;

use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;

class ConversationIngestionResult
{
    public function __construct(
        public readonly Conversation $conversation,
        public readonly ConversationMessage $message,
        public readonly bool $conversationWasCreated = false,
        public readonly bool $messageWasCreated = true,
        public readonly bool $deduplicated = false,
    ) {
    }
}
