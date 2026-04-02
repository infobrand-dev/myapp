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
        'embedding_status',
        'embedding_provider',
        'embedding_model',
        'embedding_source_hash',
        'embedding_generated_at',
        'embedding_dimensions',
        'embedding_error',
        'embedding_metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'embedding_generated_at' => 'datetime',
        'embedding_metadata' => 'array',
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
