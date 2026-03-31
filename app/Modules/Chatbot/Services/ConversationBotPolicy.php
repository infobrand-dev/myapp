<?php

namespace App\Modules\Chatbot\Services;

use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Modules\Chatbot\Models\ChatbotDecisionLog;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Support\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class ConversationBotPolicy
{
    public function evaluateInbound(
        Conversation $conversation,
        ChatbotAccount $account,
        string $incomingBody,
        array $incomingPayload = []
    ): array {
        if (!$account->autoReplyEnabled()) {
            return $this->decision('skip', 'auto_reply_disabled');
        }

        if (!$account->channelAllowed((string) $conversation->channel)) {
            return $this->decision('skip', 'channel_not_allowed');
        }

        $metadata = is_array($conversation->metadata) ? $conversation->metadata : [];
        if ((bool) ($metadata['auto_reply_paused'] ?? false)) {
            return $this->decision('skip', 'paused_for_human');
        }

        if ($this->isWithinCooldownWindow($conversation, $account)) {
            return $this->decision('skip', 'cooldown_window');
        }

        $handoffReason = $this->detectHandoffReason($incomingBody, $incomingPayload);
        if ($handoffReason !== null && $account->prefersHumanHandoff()) {
            return $this->decision('handoff', $handoffReason, null, [
                'send_handoff_ack' => $account->humanHandoffAckEnabled(),
            ]);
        }

        return $this->decision('reply', 'eligible');
    }

    public function evaluateReplyCandidate(
        Conversation $conversation,
        ChatbotAccount $account,
        string $reply,
        array $ragContexts = []
    ): array {
        $reply = trim($reply);

        if ($reply === '') {
            return $this->decision('handoff', 'ai_error', null, [
                'send_handoff_ack' => $account->humanHandoffAckEnabled(),
            ]);
        }

        if (!$account->rag_enabled) {
            return $this->decision('reply', 'reply_ready');
        }

        if (empty($ragContexts)) {
            return $this->decision('handoff', 'no_context', 0, [
                'send_handoff_ack' => $account->humanHandoffAckEnabled(),
            ]);
        }

        $topScore = (float) max(array_map(fn (array $ctx) => (float) ($ctx['score'] ?? 0), $ragContexts));

        if ($topScore < $account->minimumContextScore()) {
            return $this->decision('handoff', 'low_confidence', $topScore, [
                'send_handoff_ack' => $account->humanHandoffAckEnabled(),
            ]);
        }

        return $this->decision('reply', 'reply_ready', $topScore);
    }

    public function pauseForHuman(
        Conversation $conversation,
        string $reason,
        ?ChatbotAccount $account = null,
        array $metadata = []
    ): void {
        $state = is_array($conversation->metadata) ? $conversation->metadata : [];
        $state['needs_human'] = true;
        $state['auto_reply_paused'] = true;
        $state['handoff_reason'] = $reason;
        $state['handoff_at'] = now()->toDateTimeString();
        $state['bot_last_decision'] = 'handoff';
        $state['bot_last_decision_at'] = now()->toDateTimeString();
        $state['bot_last_reason'] = $reason;

        $conversation->update([
            'metadata' => array_merge($state, Arr::except($metadata, ['knowledge_chunk_ids', 'knowledge_document_ids'])),
        ]);

        $this->recordDecision($conversation, $account, 'handoff', $reason, $metadata);
    }

    public function markReplySent(
        Conversation $conversation,
        ?ChatbotAccount $account = null,
        ?float $confidenceScore = null,
        array $metadata = []
    ): void {
        $state = is_array($conversation->metadata) ? $conversation->metadata : [];
        $state['bot_last_decision'] = 'reply_sent';
        $state['bot_last_decision_at'] = now()->toDateTimeString();
        $state['bot_last_reason'] = 'reply_ready';

        $conversation->update([
            'metadata' => array_merge($state, Arr::except($metadata, ['knowledge_chunk_ids', 'knowledge_document_ids'])),
        ]);

        $this->recordDecision($conversation, $account, 'reply_sent', 'reply_ready', array_merge($metadata, [
            'confidence_score' => $confidenceScore,
        ]));
    }

    public function markSkipped(
        Conversation $conversation,
        ?ChatbotAccount $account,
        string $reason,
        array $metadata = []
    ): void {
        $state = is_array($conversation->metadata) ? $conversation->metadata : [];
        $state['bot_last_decision'] = 'skip';
        $state['bot_last_decision_at'] = now()->toDateTimeString();
        $state['bot_last_reason'] = $reason;

        $conversation->update([
            'metadata' => array_merge($state, Arr::except($metadata, ['knowledge_chunk_ids', 'knowledge_document_ids'])),
        ]);

        $this->recordDecision($conversation, $account, 'skip', $reason, $metadata);
    }

    public function markError(
        Conversation $conversation,
        ?ChatbotAccount $account,
        string $reason,
        array $metadata = []
    ): void {
        $state = is_array($conversation->metadata) ? $conversation->metadata : [];
        $state['bot_last_decision'] = 'error';
        $state['bot_last_decision_at'] = now()->toDateTimeString();
        $state['bot_last_reason'] = $reason;

        $conversation->update([
            'metadata' => array_merge($state, Arr::except($metadata, ['knowledge_chunk_ids', 'knowledge_document_ids'])),
        ]);

        $this->recordDecision($conversation, $account, 'error', $reason, $metadata);
    }

    public function recordDecision(
        Conversation $conversation,
        ?ChatbotAccount $account,
        string $action,
        ?string $reason = null,
        array $metadata = []
    ): void {
        if (!Schema::hasTable('chatbot_decision_logs')) {
            return;
        }

        ChatbotDecisionLog::query()->create([
            'tenant_id' => (int) ($conversation->tenant_id ?: TenantContext::currentId()),
            'conversation_id' => $conversation->id,
            'chatbot_account_id' => $account?->id,
            'channel' => $conversation->channel,
            'action' => $action,
            'reason' => $reason,
            'confidence_score' => isset($metadata['confidence_score']) ? (float) $metadata['confidence_score'] : null,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    private function isWithinCooldownWindow(Conversation $conversation, ChatbotAccount $account): bool
    {
        $cooldown = $account->replyCooldownSeconds();
        if ($cooldown <= 0) {
            return false;
        }

        $lastOutgoingAt = $conversation->last_outgoing_at;
        if (!$lastOutgoingAt) {
            return false;
        }

        return $lastOutgoingAt->greaterThan(now()->subSeconds($cooldown));
    }

    private function detectHandoffReason(string $incomingBody, array $incomingPayload = []): ?string
    {
        if ($this->isHumanHandoffInteractiveReply($incomingPayload)) {
            return 'user_requested_human';
        }

        $haystack = mb_strtolower(trim($incomingBody));
        if ($haystack === '') {
            return null;
        }

        $keywords = [
            'agent',
            'admin',
            'operator',
            'manusia',
            'human',
            'cs',
            'customer service',
            'staff',
        ];

        foreach ($keywords as $keyword) {
            if (mb_stripos($haystack, $keyword) !== false) {
                return 'user_requested_human';
            }
        }

        return null;
    }

    private function isHumanHandoffInteractiveReply(array $payload): bool
    {
        $interactiveType = strtolower((string) Arr::get($payload, 'interactive.type', ''));
        if (!in_array($interactiveType, ['button_reply', 'list_reply'], true)) {
            return false;
        }

        $id = trim((string) (
            Arr::get($payload, 'interactive.button_reply.id')
            ?: Arr::get($payload, 'interactive.list_reply.id')
        ));

        if ($id === '') {
            return false;
        }

        $normalizedId = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $id), '_'));

        return in_array($normalizedId, [
            'handoff_human',
            'request_human',
            'hubungi_human',
            'hubungi_cs',
            'hubungi_admin',
            'connect_human',
            'connect_agent',
            'talk_to_human',
            'talk_to_agent',
        ], true);
    }

    private function decision(string $action, string $reason, ?float $confidenceScore = null, array $extra = []): array
    {
        return array_merge([
            'action' => $action,
            'reason' => $reason,
            'confidence_score' => $confidenceScore,
            'send_handoff_ack' => false,
        ], $extra);
    }
}
