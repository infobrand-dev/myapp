<?php

namespace App\Modules\Xendit\Models;

use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class XenditSetting extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $table = 'xendit_settings';

    protected $fillable = [
        'tenant_id',
        'environment',
        'secret_key',
        'webhook_token',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getSecretKeyAttribute(?string $value): ?string
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

    public function setSecretKeyAttribute(?string $value): void
    {
        $this->attributes['secret_key'] = $value ? encrypt($value) : null;
    }

    public function getWebhookTokenAttribute(?string $value): ?string
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

    public function setWebhookTokenAttribute(?string $value): void
    {
        $this->attributes['webhook_token'] = $value ? encrypt($value) : null;
    }

    public static function forCurrentTenant(): ?self
    {
        $tenantId = TenantContext::currentId();

        if (!Schema::hasTable((new static())->getTable())) {
            return null;
        }

        return static::query()
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function getApiBaseUrl(): string
    {
        return $this->environment === 'production'
            ? 'https://api.xendit.co'
            : 'https://api.xendit.co';
    }
}
