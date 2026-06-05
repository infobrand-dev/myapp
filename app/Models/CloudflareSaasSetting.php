<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CloudflareSaasSetting extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'account_id',
        'zone_id',
        'api_token',
        'fallback_origin_hostname',
        'cname_target',
        'apex_proxying_enabled',
        'apex_ipv4_targets',
        'apex_ipv6_targets',
        'is_active',
        'last_health_checked_at',
        'last_error_summary',
        'meta',
    ];

    protected $casts = [
        'api_token' => 'encrypted',
        'apex_proxying_enabled' => 'boolean',
        'apex_ipv4_targets' => 'array',
        'apex_ipv6_targets' => 'array',
        'is_active' => 'boolean',
        'last_health_checked_at' => 'datetime',
        'meta' => 'array',
    ];

    public static function current(): self
    {
        return static::query()->first() ?: static::query()->firstOrCreate(['id' => 1], [
            'is_active' => false,
            'apex_proxying_enabled' => false,
        ]);
    }
}
