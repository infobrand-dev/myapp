<?php

namespace App\Modules\Conversations\Contracts;

use App\Modules\Conversations\Models\Conversation;
use Illuminate\Support\Collection;

interface ConversationChannelManager
{
    public function register(string $channel, array $capabilities): void;

    public function defaultMessageType(Conversation $conversation): string;

    public function preflightSendError(Conversation $conversation): ?string;

    public function validateTextSend(Conversation $conversation): ?string;

    public function validateMediaSend(Conversation $conversation, string $publicUrl): ?string;

    public function supportsTemplates(Conversation $conversation): bool;

    public function templatesFor(Conversation $conversation): Collection;

    public function findTemplate(Conversation $conversation, int $templateId): mixed;

    public function buildTemplatePayload(Conversation $conversation, mixed $template, array $params): ?array;

    public function hasUiFeature(Conversation $conversation, string $feature): bool;

    public function supportsAiStructuredReply(Conversation $conversation): bool;

    /**
     * @return array{status:string,sent_at:mixed}
     */
    public function outboundPersistenceDefaults(Conversation $conversation): array;
}
