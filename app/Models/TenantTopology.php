<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTopology extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'tenant_server_id',
        'tenant_database_id',
        'server_key',
        'database_key',
        'schema_name',
        'isolation_mode',
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

    public function server(): BelongsTo
    {
        return $this->belongsTo(TenantServer::class, 'tenant_server_id');
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(TenantDatabase::class, 'tenant_database_id');
    }
}
