<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoredFileAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'stored_file_id',
        'tenant_id',
        'company_id',
        'branch_id',
        'user_id',
        'action',
        'was_authorized',
        'ip_address',
        'user_agent',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'was_authorized' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function storedFile(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class);
    }
}
