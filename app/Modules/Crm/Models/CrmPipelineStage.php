<?php

namespace App\Modules\Crm\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmPipelineStage extends Model
{
    protected $fillable = [
        'pipeline_id',
        'tenant_id',
        'name',
        'code',
        'position',
        'probability_default',
        'stage_type',
        'color_token',
        'meta',
    ];

    protected $casts = [
        'position' => 'integer',
        'probability_default' => 'integer',
        'meta' => 'array',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id')->where('tenant_id', TenantContext::currentId());
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'stage_id')->where('tenant_id', TenantContext::currentId());
    }
}
