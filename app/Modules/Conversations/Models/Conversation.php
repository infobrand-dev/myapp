<?php

namespace App\Modules\Conversations\Models;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'instance_id',
        'channel',
        'external_id',
        'contact_external_id',
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
        $waInstanceClass = \App\Modules\WhatsAppApi\Models\WhatsAppInstance::class;
        if (class_exists($waInstanceClass)) {
            return $this->belongsTo($waInstanceClass, 'instance_id')
                ->where('tenant_id', TenantContext::currentId());
        }

        // Fallback dummy relation to keep conversations module bootable without WhatsAppApi module.
        return $this->belongsTo(User::class, 'instance_id')->whereRaw('1 = 0');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)
            ->where('tenant_id', TenantContext::currentId());
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ConversationMessage::class)->ofMany([
            'created_at' => 'max',
            'id' => 'max',
        ], function ($query) {
            $query->where('tenant_id', TenantContext::currentId())
                ->whereNotNull('created_at');
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}

