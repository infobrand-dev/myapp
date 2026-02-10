<?php

namespace App\Modules\Conversations\Events;

use App\Modules\Conversations\Models\ConversationMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ConversationMessageCreated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public ConversationMessage $message;

    public function __construct(ConversationMessage $message)
    {
        $this->message = $message->load('conversation', 'user');
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('conversations.' . $this->message->conversation_id);
    }
}
