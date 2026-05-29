<?php

namespace App\Modules\Tripay\Models;

use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TripaySetting extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $table = 'tripay_settings';

    protected $fillable = [
        'tenant_id',
        'environment',
        'api_key',
        'private_key',
        'merchant_code',
        'callback_signature_key',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function decryptValue(?string $value): ?string
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

    public function getApiKeyAttribute(?string $value): ?string
    {
        return $this->decryptValue($value);
    }

    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value ? encrypt($value) : null;
    }

    public function getPrivateKeyAttribute(?string $value): ?string
    {
        return $this->decryptValue($value);
    }

    public function setPrivateKeyAttribute(?string $value): void
    {
        $this->attributes['private_key'] = $value ? encrypt($value) : null;
    }

    public function getCallbackSignatureKeyAttribute(?string $value): ?string
    {
        return $this->decryptValue($value);
    }

    public function setCallbackSignatureKeyAttribute(?string $value): void
    {
        $this->attributes['callback_signature_key'] = $value ? encrypt($value) : null;
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
            ? 'https://tripay.co.id/api'
            : 'https://tripay.co.id/api-sandbox';
    }
}
