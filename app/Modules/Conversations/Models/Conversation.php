<?php

namespace App\Modules\Conversations\Models;

use App\Models\User;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'instance_id',
        'channel',
        'external_id',
        'contact_wa_id',
        'contact_name',
        'status',
        'owner_id',
        'claimed_at',
        'locked_until',
        'last_message_at',
        'last_incoming_at',
        'last_outgoing_at',
        'unread_count',
        'metadata',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_message_at' => 'datetime',
        'last_incoming_at' => 'datetime',
        'last_outgoing_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'instance_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }
}
