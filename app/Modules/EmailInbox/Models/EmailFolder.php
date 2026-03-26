<?php

namespace App\Modules\EmailInbox\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailFolder extends Model
{
    protected $fillable = [
        'tenant_id',
        'account_id',
        'remote_id',
        'name',
        'type',
        'is_selectable',
        'last_uid',
    ];

    protected $casts = [
        'is_selectable' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'account_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'folder_id')
            ->where('tenant_id', TenantContext::currentId());
    }
}
