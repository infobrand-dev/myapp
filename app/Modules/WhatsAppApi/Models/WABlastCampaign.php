<?php

namespace App\Modules\WhatsAppApi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WABlastCampaign extends Model
{
    use HasFactory;

    protected $table = 'wa_blast_campaigns';

    protected $fillable = [
        'tenant_id',
        'name',
        'instance_id',
        'template_id',
        'created_by',
        'status',
        'total_count',
        'queued_count',
        'sent_count',
        'failed_count',
        'scheduled_at',
        'started_at',
        'finished_at',
        'last_error',
        'settings',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'settings' => 'array',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'instance_id')
            ->where('tenant_id', 1);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WATemplate::class, 'template_id')
            ->where('tenant_id', 1);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WABlastRecipient::class, 'campaign_id')
            ->where('tenant_id', 1);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', 1)
            ->firstOrFail();
    }
}
