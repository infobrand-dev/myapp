<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRuntimeTopology extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'app_server_id',
        'app_server_key',
        'queue_cluster',
        'realtime_cluster',
        'scheduler_cluster',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function appServer(): BelongsTo
    {
        return $this->belongsTo(AppServer::class, 'app_server_id');
    }
}
