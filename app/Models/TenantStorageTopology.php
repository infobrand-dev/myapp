<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantStorageTopology extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'storage_server_id',
        'storage_bucket_id',
        'storage_server_key',
        'storage_bucket_key',
        'disk',
        'visibility',
        'base_path',
        'is_default',
        'status',
        'meta',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function storageServer(): BelongsTo
    {
        return $this->belongsTo(StorageServer::class, 'storage_server_id');
    }

    public function storageBucket(): BelongsTo
    {
        return $this->belongsTo(StorageBucket::class, 'storage_bucket_id');
    }
}
