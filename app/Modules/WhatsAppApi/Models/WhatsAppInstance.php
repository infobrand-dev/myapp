<?php

namespace App\Modules\WhatsAppApi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppInstance extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_instances';

    protected $fillable = [
        'name',
        'phone_number',
        'provider',
        'api_base_url',
        'api_token',
        'webhook_url',
        'status',
        'is_active',
        'settings',
        'last_health_check_at',
        'last_error',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'last_health_check_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'whatsapp_instance_user')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'instance_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
