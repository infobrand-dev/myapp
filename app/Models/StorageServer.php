<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageServer extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'key',
        'name',
        'driver',
        'provider',
        'region',
        'endpoint',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function buckets(): HasMany
    {
        return $this->hasMany(StorageBucket::class);
    }
}
