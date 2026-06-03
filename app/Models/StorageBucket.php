<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageBucket extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'storage_server_id',
        'key',
        'name',
        'disk',
        'visibility',
        'region',
        'base_path',
        'cdn_url',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(StorageServer::class, 'storage_server_id');
    }

    public function storageTopologies(): HasMany
    {
        return $this->hasMany(TenantStorageTopology::class);
    }
}
