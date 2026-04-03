<?php

namespace App\Modules\Conversations\Models;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConversationParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'user_id',
        'role',
        'unread_count',
        'invited_by',
        'invited_at',
        'last_read_at',
        'left_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'invited_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class)
            ->where('tenant_id', TenantContext::currentId());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
