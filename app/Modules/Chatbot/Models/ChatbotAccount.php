<?php

namespace App\Modules\Chatbot\Models;

use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ChatbotAccount extends Model
{
    use HasFactory;
    use NormalizesPgsqlBooleanAttributes;

    protected $table = 'chatbot_accounts';

    protected $fillable = [
        'tenant_id',
        'name',
        'access_scope',
        'provider',
        'model',
        'ai_source',
        'automation_mode',
        'system_prompt',
        'focus_scope',
        'response_style',
        'operation_mode',
        'api_key',
        'status',
        'mirror_to_conversations',
        'rag_enabled',
        'rag_top_k',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'metadata' => 'array',
        'ai_source' => 'string',
        'access_scope' => 'string',
        'mirror_to_conversations' => 'boolean',
        'rag_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $account): void {
            if (!$account->tenant_id) {
                $account->tenant_id = TenantContext::currentId();
            }
        });
    }

    public function getApiKeyAttribute($value)
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        return Crypt::decryptString($value);
    }

    public function setApiKeyAttribute($value): void
    {
        if ($value === null || trim($value) === '') {
            $this->attributes['api_key'] = null;
            return;
        }

        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function knowledgeDocuments(): HasMany
    {
        return $this->hasMany(ChatbotKnowledgeDocument::class, 'chatbot_account_id');
    }

    public function knowledgeChunks(): HasMany
    {
        return $this->hasMany(ChatbotKnowledgeChunk::class, 'chatbot_account_id');
    }

    public function automationMode(): string
    {
        return strtolower((string) ($this->automation_mode ?: 'ai_first'));
    }

    public function accessScope(): string
    {
        return strtolower((string) ($this->access_scope ?: 'public'));
    }

    public function isPrivate(): bool
    {
        return $this->accessScope() === 'private';
    }

    public function isPublic(): bool
    {
        return !$this->isPrivate();
    }

    public function availableForExternalChannels(): bool
    {
        return $this->isPublic() && strtolower((string) ($this->status ?: 'inactive')) === 'active';
    }

    public function behaviorMode(): string
    {
        if ($this->isRuleOnly()) {
            return 'rule_only';
        }

        if (strtolower((string) ($this->operation_mode ?: 'ai_only')) === 'ai_then_human') {
            return 'ai_then_human';
        }

        return 'ai_only';
    }

    public function usesAi(): bool
    {
        return $this->behaviorMode() !== 'rule_only';
    }

    public function isRuleOnly(): bool
    {
        return $this->automationMode() === 'rule_only';
    }

    public function aiSource(): string
    {
        return strtolower((string) ($this->ai_source ?: 'managed'));
    }

    public function isByoAi(): bool
    {
        return $this->aiSource() === 'byo';
    }

    public function isManagedAi(): bool
    {
        return !$this->isByoAi();
    }

    public function runtimeProvider(): string
    {
        if ($this->isManagedAi()) {
            return 'openai';
        }

        return strtolower((string) ($this->provider ?: 'openai'));
    }

    public function runtimeApiKey(): ?string
    {
        if ($this->isManagedAi()) {
            return trim((string) config('services.openai.api_key')) ?: ($this->api_key ?: null);
        }

        return $this->api_key ?: null;
    }

    /**
     * @return array<int, string>
     */
    public function byoAllowedProviders(): array
    {
        $subscription = TenantSubscription::query()
            ->where('tenant_id', $this->tenant_id ?: TenantContext::currentId())
            ->where('status', 'active')
            ->latest('id')
            ->first();
        $providers = Arr::get(is_array($subscription?->meta) ? $subscription->meta : [], 'byo_ai.allowed_providers');

        return array_values(array_filter((array) $providers, fn ($provider) => is_string($provider) && trim($provider) !== ''));
    }

    public function byoProviderAllowed(?string $provider = null): bool
    {
        if ($this->isManagedAi()) {
            return true;
        }

        $provider = strtolower((string) ($provider ?: $this->runtimeProvider()));
        $allowedProviders = $this->byoAllowedProviders();

        if ($allowedProviders === []) {
            return false;
        }

        return in_array($provider, $allowedProviders, true);
    }

    public function botConfig(): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        return array_merge([
            'auto_reply_enabled' => true,
            'allowed_channels' => ['wa_api', 'social_dm'],
            'allow_interactive_buttons' => true,
            'human_handoff_ack_enabled' => true,
            'minimum_context_score' => 4,
            'reply_cooldown_seconds' => 30,
            'max_bot_replies_per_conversation' => 0,
            'knowledge_usage_mode' => $this->rag_enabled ? 'required' : 'optional',
        ], is_array($metadata['bot_config'] ?? null) ? $metadata['bot_config'] : []);
    }

    public function autoReplyEnabled(): bool
    {
        return (bool) ($this->botConfig()['auto_reply_enabled'] ?? true);
    }

    public function channelAllowed(string $channel): bool
    {
        $allowed = array_values(array_filter((array) ($this->botConfig()['allowed_channels'] ?? [])));

        return in_array($channel, $allowed, true);
    }

    public function allowInteractiveButtons(): bool
    {
        return (bool) ($this->botConfig()['allow_interactive_buttons'] ?? true);
    }

    public function humanHandoffAckEnabled(): bool
    {
        return (bool) ($this->botConfig()['human_handoff_ack_enabled'] ?? true);
    }

    public function minimumContextScore(): float
    {
        return (float) ($this->botConfig()['minimum_context_score'] ?? 4);
    }

    public function replyCooldownSeconds(): int
    {
        return (int) ($this->botConfig()['reply_cooldown_seconds'] ?? 30);
    }

    public function maxBotRepliesPerConversation(): int
    {
        return max(0, (int) ($this->botConfig()['max_bot_replies_per_conversation'] ?? 0));
    }

    public function prefersHumanHandoff(): bool
    {
        return $this->behaviorMode() === 'ai_then_human';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
