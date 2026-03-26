<?php

namespace App\Modules\EmailInbox\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailAccount extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'name',
        'email_address',
        'provider',
        'direction_mode',
        'inbound_protocol',
        'inbound_host',
        'inbound_port',
        'inbound_encryption',
        'inbound_username',
        'inbound_password',
        'inbound_validate_cert',
        'outbound_host',
        'outbound_port',
        'outbound_encryption',
        'outbound_username',
        'outbound_password',
        'outbound_from_name',
        'outbound_reply_to',
        'sync_enabled',
        'sync_status',
        'last_synced_at',
        'last_error_at',
        'last_error_message',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'inbound_validate_cert' => 'boolean',
        'sync_enabled' => 'boolean',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
        'last_error_at' => 'datetime',
        'inbound_password' => 'encrypted',
        'outbound_password' => 'encrypted',
    ];

    public function folders(): HasMany
    {
        return $this->hasMany(EmailFolder::class, 'account_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'account_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(EmailSyncRun::class, 'account_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
