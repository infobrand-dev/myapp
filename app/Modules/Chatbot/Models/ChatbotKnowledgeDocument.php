<?php

namespace App\Modules\Chatbot\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotKnowledgeDocument extends Model
{
    use HasFactory;

    protected $table = 'chatbot_knowledge_documents';

    protected $fillable = [
        'chatbot_account_id',
        'title',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function chatbotAccount(): BelongsTo
    {
        return $this->belongsTo(ChatbotAccount::class, 'chatbot_account_id');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(ChatbotKnowledgeChunk::class, 'document_id');
    }
}

