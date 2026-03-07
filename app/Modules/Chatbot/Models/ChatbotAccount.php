<?php

namespace App\Modules\Chatbot\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ChatbotAccount extends Model
{
    use HasFactory;

    protected $table = 'chatbot_accounts';

    protected $fillable = [
        'name',
        'provider',
        'model',
        'system_prompt',
        'focus_scope',
        'response_style',
        'api_key',
        'status',
        'mirror_to_conversations',
        'rag_enabled',
        'rag_top_k',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'mirror_to_conversations' => 'boolean',
        'rag_enabled' => 'boolean',
    ];

    public function getApiKeyAttribute($value)
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // Backward compatibility for old plaintext data.
            return $value;
        }
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
}
