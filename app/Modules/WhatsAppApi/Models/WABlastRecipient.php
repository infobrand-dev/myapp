<?php

namespace App\Modules\WhatsAppApi\Models;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WABlastRecipient extends Model
{
    use HasFactory;

    protected $table = 'wa_blast_recipients';

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'phone_number',
        'contact_name',
        'variables',
        'status',
        'error_message',
        'conversation_id',
        'message_id',
        'queued_at',
        'sent_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WABlastCampaign::class, 'campaign_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function conversation(): BelongsTo
    {
        $conversationClass = \App\Modules\Conversations\Models\Conversation::class;
        if (class_exists($conversationClass)) {
            return $this->belongsTo($conversationClass, 'conversation_id')
                ->where('tenant_id', TenantContext::currentId());
        }

        return $this->belongsTo(User::class, 'conversation_id')->whereRaw('1 = 0');
    }

    public function message(): BelongsTo
    {
        $messageClass = \App\Modules\Conversations\Models\ConversationMessage::class;
        if (class_exists($messageClass)) {
            return $this->belongsTo($messageClass, 'message_id')
                ->where('tenant_id', TenantContext::currentId());
        }

        return $this->belongsTo(User::class, 'message_id')->whereRaw('1 = 0');
    }
}
