<?php

namespace App\Modules\Biteship\Models;

use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class BiteshipSetting extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $table = 'biteship_settings';

    protected $fillable = [
        'tenant_id',
        'environment',
        'api_key',
        'default_couriers',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_couriers' => 'array',
    ];

    public function getApiKeyAttribute(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value ? encrypt($value) : null;
    }

    public static function forCurrentTenant(): ?self
    {
        if (!Schema::hasTable((new static())->getTable())) {
            return null;
        }

        return static::query()
            ->where('tenant_id', TenantContext::currentId())
            ->first();
    }

    public function getApiBaseUrl(): string
    {
        return $this->environment === 'production'
            ? 'https://api.biteship.com'
            : 'https://api.biteship.com';
    }
}
