<?php

namespace App\Modules\WhatsAppApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WAFlow extends Model
{
    protected $table = 'wa_flows';

    protected $fillable = [
        'tenant_id',
        'instance_id',
        'name',
        'categories',
        'endpoint_uri',
        'meta_flow_id',
        'status',
        'json_version',
        'data_api_version',
        'validation_errors',
        'health_status',
        'flow_json',
        'preview_url',
        'preview_expires_at',
        'last_sync_error',
    ];

    protected $casts = [
        'categories' => 'array',
        'validation_errors' => 'array',
        'health_status' => 'array',
        'preview_expires_at' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'instance_id')
            ->where('tenant_id', 1);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', 1)
            ->firstOrFail();
    }
}
