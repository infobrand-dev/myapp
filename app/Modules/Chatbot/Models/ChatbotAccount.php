<?php

namespace App\Modules\Chatbot\Models;

use App\Models\User;
use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
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
        'provider',
        'model',
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

    public function usesAi(): bool
    {
        return in_array($this->automationMode(), ['ai_assisted', 'ai_first'], true);
    }

    public function isRuleOnly(): bool
    {
        return $this->automationMode() === 'rule_only';
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

    public function prefersHumanHandoff(): bool
    {
        return strtolower((string) ($this->operation_mode ?: 'ai_only')) === 'ai_then_human';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
