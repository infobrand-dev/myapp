<?php

namespace App\Modules\WhatsAppWeb\Models;

use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class WhatsAppWebSetting extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $table = 'whatsapp_web_settings';
    protected $fillable = [
        'tenant_id',
        'provider',
        'base_url',
        'verify_token',
        'default_sender_name',
        'is_active',
        'timeout_seconds',
        'notes',
        'created_by',
        'updated_by',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_active' => 'boolean',
        'timeout_seconds' => 'integer',
        'last_tested_at' => 'datetime',
    ];

    public function getVerifyTokenAttribute($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    public function setVerifyTokenAttribute($value): void
    {
        $trimmed = trim((string) $value);
        $this->attributes['verify_token'] = $trimmed !== '' ? encrypt($trimmed) : null;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
