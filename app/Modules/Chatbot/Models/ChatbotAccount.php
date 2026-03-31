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

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
