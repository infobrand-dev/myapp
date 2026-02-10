<?php

namespace App\Modules\SocialMedia\Jobs;

use App\Modules\Conversations\Models\ConversationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSocialMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $messageId;

    public function __construct(int $messageId)
    {
        $this->messageId = $messageId;
    }

    public function handle(): void
    {
        $message = ConversationMessage::with('conversation')->find($this->messageId);
        if (!$message || !$message->conversation || $message->conversation->channel !== 'social_dm') {
            return;
        }

        // Placeholder: integrate with Meta Graph or external provider here
        Log::info('SendSocialMessage dispatched', [
            'message_id' => $message->id,
            'contact' => $message->conversation->contact_wa_id,
            'body' => $message->body,
        ]);

        $message->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
