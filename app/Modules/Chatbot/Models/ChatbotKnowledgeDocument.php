<?php

namespace App\Modules\Chatbot\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\TenantContext;

class ChatbotKnowledgeDocument extends Model
{
    use HasFactory;

    protected $table = 'chatbot_knowledge_documents';

    protected $fillable = [
        'tenant_id',
        'chatbot_account_id',
        'title',
        'content',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $document): void {
            if (!$document->tenant_id) {
                $document->tenant_id = TenantContext::currentId();
            }
        });
    }

    public function chatbotAccount(): BelongsTo
    {
        return $this->belongsTo(ChatbotAccount::class, 'chatbot_account_id');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(ChatbotKnowledgeChunk::class, 'document_id');
    }
}
