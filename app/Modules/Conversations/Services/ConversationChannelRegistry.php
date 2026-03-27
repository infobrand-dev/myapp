<?php

namespace App\Modules\Conversations\Services;

use App\Modules\Conversations\Contracts\ConversationChannelManager;
use App\Modules\Conversations\Models\Conversation;
use Illuminate\Support\Collection;

class ConversationChannelRegistry implements ConversationChannelManager
{
    /**
     * @var array<string, array<string, callable|mixed>>
     */
    private array $capabilities = [];

    public function register(string $channel, array $capabilities): void
    {
        $this->capabilities[$channel] = $capabilities;
    }

    public function defaultMessageType(Conversation $conversation): string
    {
        return (string) $this->resolve($conversation, 'default_message_type', 'text');
    }

    public function preflightSendError(Conversation $conversation): ?string
    {
        return $this->resolveNullableString($conversation, 'preflight_send_error');
    }

    public function validateTextSend(Conversation $conversation): ?string
    {
        return $this->resolveNullableString($conversation, 'validate_text_send');
    }

    public function validateMediaSend(Conversation $conversation, string $publicUrl): ?string
    {
        return $this->resolveNullableString($conversation, 'validate_media_send', $publicUrl);
    }

    public function supportsTemplates(Conversation $conversation): bool
    {
        return (bool) $this->resolve($conversation, 'supports_templates', false);
    }

    public function templatesFor(Conversation $conversation): Collection
    {
        $templates = $this->resolve($conversation, 'templates', collect());

        return $templates instanceof Collection ? $templates : collect($templates);
    }

    public function findTemplate(Conversation $conversation, int $templateId): mixed
    {
        return $this->resolve($conversation, 'find_template', null, $templateId);
    }

    public function buildTemplatePayload(Conversation $conversation, mixed $template, array $params): ?array
    {
        $payload = $this->resolve($conversation, 'build_template_payload', null, $template, $params);

        return is_array($payload) ? $payload : null;
    }

    public function hasUiFeature(Conversation $conversation, string $feature): bool
    {
        $features = $this->resolve($conversation, 'ui_features', []);
        if (!is_array($features)) {
            return false;
        }

        return (bool) ($features[$feature] ?? false);
    }

    public function supportsAiStructuredReply(Conversation $conversation): bool
    {
        return (bool) $this->resolve($conversation, 'supports_ai_structured_reply', false);
    }

    public function outboundPersistenceDefaults(Conversation $conversation): array
    {
        $defaults = $this->resolve($conversation, 'outbound_persistence_defaults', null);
        if (is_array($defaults) && array_key_exists('status', $defaults)) {
            return $defaults;
        }

        return [
            'status' => 'sent',
            'sent_at' => now(),
        ];
    }

    private function resolveNullableString(Conversation $conversation, string $key, ...$args): ?string
    {
        $value = $this->resolve($conversation, $key, null, ...$args);
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function resolve(Conversation $conversation, string $key, mixed $default = null, ...$args): mixed
    {
        $capability = $this->capabilities[$conversation->channel][$key] ?? null;
        if (is_callable($capability)) {
            return $capability($conversation, ...$args);
        }

        return $capability ?? $default;
    }
}
