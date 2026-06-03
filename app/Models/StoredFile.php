<?php

namespace App\Models;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoredFile extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'storage_profile_id',
        'disk',
        'directory',
        'path',
        'visibility',
        'availability_status',
        'category',
        'access_class',
        'share_strategy',
        'retention_class',
        'provider_origin',
        'provider_media_id',
        'provider_media_url',
        'expires_at',
        'origin_system',
        'origin_owner',
        'source_module',
        'source_context',
        'storage_driver',
        'storage_bucket',
        'storage_region',
        'storage_endpoint',
        'storage_url',
        'storage_root',
        'storage_snapshot',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'content_hash',
        'uploaded_by',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'storage_snapshot' => 'array',
        'expires_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function storageProfile(): BelongsTo
    {
        return $this->belongsTo(StorageProfile::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(StoredFileAccessLog::class);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $query = $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId());

        if (CompanyContext::currentId()) {
            $query->where(function ($builder) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', CompanyContext::currentId());
            });
        }

        if (BranchContext::currentId() !== null) {
            $query->where(function ($builder) {
                $builder->whereNull('branch_id')
                    ->orWhere('branch_id', BranchContext::currentId());
            });
        }

        return $query->firstOrFail();
    }
}
