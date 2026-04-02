<?php

namespace App\Modules\Chatbot\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    use HasFactory;

    protected $table = 'chatbot_messages';

    protected $fillable = [
        'tenant_id',
        'session_id',
        'role',
        'content',
        'provider_response',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'provider_response' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatbotSession::class, 'session_id');
    }
}
