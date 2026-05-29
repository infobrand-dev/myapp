<?php

namespace App\Models;

use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class TenantPaymentGateway extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'provider',
        'display_name',
        'is_enabled',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'meta' => 'array',
    ];

    public static function activeForCurrentContext(): ?self
    {
        return static::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('is_enabled', true)
            ->latest('id')
            ->first();
    }
}
