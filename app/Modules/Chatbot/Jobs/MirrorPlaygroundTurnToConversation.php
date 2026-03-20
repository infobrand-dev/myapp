<?php

namespace App\Modules\Chatbot\Jobs;

use App\Modules\Chatbot\Models\ChatbotMessage;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;

class MirrorPlaygroundTurnToConversation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $chatbotAccountId;
    public int $userId;
    public int $sessionId;
    /** @var int[] */
    public array $chatbotMessageIds;

    public function __construct(int $chatbotAccountId, int $userId, int $sessionId, array $chatbotMessageIds)
    {
        $this->chatbotAccountId = $chatbotAccountId;
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->chatbotMessageIds = array_values(array_unique(array_map('intval', $chatbotMessageIds)));
    }

    public function handle(): void
    {
        $conversationClass = \App\Modules\Conversations\Models\Conversation::class;
        $messageClass = \App\Modules\Conversations\Models\ConversationMessage::class;

        if (!class_exists($conversationClass) || !class_exists($messageClass)) {
            return;
        }

        if (!Schema::hasTable('conversations') || !Schema::hasTable('conversation_messages')) {
            return;
        }

        $conversation = $conversationClass::query()->firstOrCreate(
            [
                'tenant_id' => $this->tenantId(),
                'channel' => 'chatbot_playground',
                'instance_id' => $this->chatbotAccountId,
                'contact_external_id' => 'user:' . $this->userId,
            ],
            [
                'tenant_id' => $this->tenantId(),
                'contact_name' => 'User ' . $this->userId,
                'status' => 'open',
                'last_message_at' => now(),
                'last_incoming_at' => null,
                'last_outgoing_at' => null,
                'unread_count' => 0,
                'metadata' => ['source' => 'chatbot_playground'],
            ]
        );

        foreach ($this->chatbotMessageIds as $chatbotMessageId) {
            $chatbotMessage = ChatbotMessage::query()->find($chatbotMessageId);
            if (!$chatbotMessage || (int) $chatbotMessage->session_id !== $this->sessionId) {
                continue;
            }

            $externalId = 'chatbot_playground:' . $chatbotMessageId;
            $exists = $messageClass::query()
                ->where('tenant_id', $this->tenantId())
                ->where('conversation_id', $conversation->id)
                ->where('external_message_id', $externalId)
                ->exists();

            if ($exists) {
                continue;
            }

            $isIncoming = $chatbotMessage->role !== 'assistant';

            $messageClass::query()->create([
                'tenant_id' => $this->tenantId(),
                'conversation_id' => $conversation->id,
                'user_id' => $isIncoming ? $this->userId : null,
                'direction' => $isIncoming ? 'in' : 'out',
                'type' => 'text',
                'body' => $chatbotMessage->content,
                'status' => $isIncoming ? 'delivered' : 'sent',
                'external_message_id' => $externalId,
                'payload' => [
                    'source' => 'chatbot_playground',
                    'chatbot_session_id' => $this->sessionId,
                    'chatbot_message_id' => $chatbotMessageId,
                ],
                'sent_at' => $isIncoming ? null : now(),
            ]);

            $updates = [
                'last_message_at' => now(),
            ];
            if ($isIncoming) {
                $updates['last_incoming_at'] = now();
                $updates['unread_count'] = ((int) $conversation->unread_count) + 1;
            } else {
                $updates['last_outgoing_at'] = now();
            }

            $conversation->update($updates);
            $conversation->refresh();
        }
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }
}
