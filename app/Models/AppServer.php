<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppServer extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'key',
        'name',
        'region',
        'base_url',
        'queue_cluster',
        'realtime_cluster',
        'scheduler_cluster',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function runtimeTopologies(): HasMany
    {
        return $this->hasMany(TenantRuntimeTopology::class, 'app_server_id');
    }
}
