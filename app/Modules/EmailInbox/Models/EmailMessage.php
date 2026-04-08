<?php

namespace App\Modules\EmailInbox\Models;

use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMessage extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $fillable = [
        'tenant_id',
        'account_id',
        'folder_id',
        'direction',
        'status',
        'message_id',
        'in_reply_to',
        'subject',
        'from_name',
        'from_email',
        'to_json',
        'cc_json',
        'bcc_json',
        'reply_to_json',
        'sent_at',
        'received_at',
        'read_at',
        'is_read',
        'has_attachments',
        'body_text',
        'body_html',
        'raw_headers',
        'metadata',
        'sync_uid',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'to_json' => 'array',
        'cc_json' => 'array',
        'bcc_json' => 'array',
        'reply_to_json' => 'array',
        'raw_headers' => 'array',
        'metadata' => 'array',
        'is_read' => 'boolean',
        'has_attachments' => 'boolean',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'account_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(EmailFolder::class, 'folder_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EmailMessageAttachment::class, 'message_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
