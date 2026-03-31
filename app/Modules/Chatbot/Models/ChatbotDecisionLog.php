<?php

namespace App\Modules\Chatbot\Models;

use App\Modules\Conversations\Models\Conversation;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotDecisionLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'chatbot_decision_logs';

    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'chatbot_account_id',
        'channel',
        'action',
        'reason',
        'confidence_score',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'conversation_id' => 'integer',
        'chatbot_account_id' => 'integer',
        'confidence_score' => 'float',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class)
            ->where('tenant_id', TenantContext::currentId());
    }

    public function chatbotAccount(): BelongsTo
    {
        return $this->belongsTo(ChatbotAccount::class, 'chatbot_account_id');
    }
}
