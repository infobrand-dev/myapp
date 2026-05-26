<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class TenantTransactionalMailSetting extends Model
{
    public const DELIVERY_MODE_MANAGED = 'managed';
    public const DELIVERY_MODE_CUSTOM_SMTP = 'custom_smtp';

    protected $fillable = [
        'tenant_id',
        'is_enabled',
        'delivery_mode',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'from_name',
        'from_email',
        'reply_to',
        'last_tested_at',
        'last_test_status',
        'last_test_error',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'smtp_password' => 'encrypted',
        'last_tested_at' => 'datetime',
    ];

    public static function forCurrentTenant(): ?self
    {
        return static::query()
            ->where('tenant_id', TenantContext::currentId())
            ->first();
    }

    public function deliveryMode(): string
    {
        return $this->delivery_mode ?: self::DELIVERY_MODE_MANAGED;
    }
}
