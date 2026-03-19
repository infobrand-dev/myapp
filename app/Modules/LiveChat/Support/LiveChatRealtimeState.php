<?php

namespace App\Modules\LiveChat\Support;

use Illuminate\Support\Facades\Cache;

class LiveChatRealtimeState
{
    public function markVisitorTyping(int $widgetId, string $visitorKey, int $seconds = 8): void
    {
        if ($widgetId <= 0 || trim($visitorKey) === '') {
            return;
        }

        Cache::put($this->visitorTypingKey($widgetId, $visitorKey), true, now()->addSeconds($seconds));
    }

    public function markAgentTyping(int $conversationId, int $userId, int $seconds = 8): void
    {
        if ($conversationId <= 0 || $userId <= 0) {
            return;
        }

        Cache::put($this->agentTypingKey($conversationId, $userId), true, now()->addSeconds($seconds));
    }

    public function isVisitorTyping(int $widgetId, string $visitorKey): bool
    {
        if ($widgetId <= 0 || trim($visitorKey) === '') {
            return false;
        }

        return (bool) Cache::get($this->visitorTypingKey($widgetId, $visitorKey), false);
    }

    public function isAgentTyping(int $conversationId, ?int $userId): bool
    {
        if ($conversationId <= 0 || !$userId) {
            return false;
        }

        return (bool) Cache::get($this->agentTypingKey($conversationId, $userId), false);
    }

    private function visitorTypingKey(int $widgetId, string $visitorKey): string
    {
        return sprintf('live_chat:typing:visitor:%d:%s', $widgetId, sha1($visitorKey));
    }

    private function agentTypingKey(int $conversationId, int $userId): string
    {
        return sprintf('live_chat:typing:agent:%d:%d', $conversationId, $userId);
    }
}
