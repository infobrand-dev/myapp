<?php

namespace App\Modules\Crm\Models;

use App\Models\Branch;
use App\Models\Company;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmPipeline extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'name',
        'code',
        'is_default',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->where('tenant_id', TenantContext::currentId());
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id')->where('tenant_id', TenantContext::currentId());
    }

    public function stages(): HasMany
    {
        return $this->hasMany(CrmPipelineStage::class, 'pipeline_id')
            ->where('tenant_id', TenantContext::currentId())
            ->orderBy('position')
            ->orderBy('id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'pipeline_id')
            ->where('tenant_id', TenantContext::currentId());
    }
}
