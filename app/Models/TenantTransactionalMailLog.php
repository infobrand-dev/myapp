<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantTransactionalMailLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'document_type',
        'document_id',
        'template_key',
        'recipient_email',
        'recipient_name',
        'subject',
        'status',
        'mailer_source',
        'queued_at',
        'sent_at',
        'error_message',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'meta' => 'array',
    ];
}
