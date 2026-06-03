<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantServer extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'key',
        'host',
        'port',
        'region',
        'provider',
        'status',
        'max_tenants',
        'current_tenants',
        'meta',
    ];

    protected $casts = [
        'max_tenants' => 'integer',
        'current_tenants' => 'integer',
        'meta' => 'array',
    ];

    public function databases(): HasMany
    {
        return $this->hasMany(TenantDatabase::class, 'server_id');
    }

    public function topologies(): HasMany
    {
        return $this->hasMany(TenantTopology::class, 'tenant_server_id');
    }
}
