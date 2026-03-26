<?php

namespace App\Modules\EmailInbox\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSyncRun extends Model
{
    protected $fillable = [
        'tenant_id',
        'account_id',
        'sync_type',
        'status',
        'started_at',
        'finished_at',
        'error_message',
        'stats_json',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'stats_json' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'account_id')
            ->where('tenant_id', TenantContext::currentId());
    }
}
