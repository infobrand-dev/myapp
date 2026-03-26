<?php

namespace App\Modules\EmailInbox\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMessageAttachment extends Model
{
    protected $fillable = [
        'tenant_id',
        'message_id',
        'disk',
        'path',
        'filename',
        'mime_type',
        'size',
        'content_id',
        'is_inline',
        'checksum',
    ];

    protected $casts = [
        'is_inline' => 'boolean',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class, 'message_id')
            ->where('tenant_id', TenantContext::currentId());
    }
}
