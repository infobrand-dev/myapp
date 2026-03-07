<?php

namespace App\Modules\Chatbot\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotKnowledgeChunk extends Model
{
    use HasFactory;

    protected $table = 'chatbot_knowledge_chunks';

    protected $fillable = [
        'document_id',
        'chatbot_account_id',
        'chunk_index',
        'content',
        'content_length',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(ChatbotKnowledgeDocument::class, 'document_id');
    }

    public function chatbotAccount(): BelongsTo
    {
        return $this->belongsTo(ChatbotAccount::class, 'chatbot_account_id');
    }
}

