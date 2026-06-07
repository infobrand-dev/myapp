<?php

namespace App\Modules\Crm\Models;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Crm\Support\CrmLeadScope;
use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class CrmLead extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'contact_id',
        'pipeline_id',
        'stage_id',
        'owner_user_id',
        'title',
        'stage',
        'priority',
        'lead_source',
        'qualification_status',
        'lead_score',
        'estimated_value',
        'currency',
        'probability',
        'expected_close_date',
        'next_follow_up_at',
        'last_contacted_at',
        'won_at',
        'lost_at',
        'notes',
        'labels',
        'meta',
        'position',
        'visibility_scope',
        'lost_reason',
        'is_archived',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'lead_score' => 'integer',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'next_follow_up_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
        'labels' => 'array',
        'meta' => 'array',
        'is_archived' => 'boolean',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id')->where('tenant_id', TenantContext::currentId());
    }

    public function stageModel(): BelongsTo
    {
        return $this->belongsTo(CrmPipelineStage::class, 'stage_id')->where('tenant_id', TenantContext::currentId());
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->where('tenant_id', TenantContext::currentId());
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id')->where('tenant_id', TenantContext::currentId());
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->where('tenant_id', TenantContext::currentId());
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id')->where('tenant_id', TenantContext::currentId());
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'lead_id')
            ->where('tenant_id', TenantContext::currentId())
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    public function followUpTasks(): HasMany
    {
        return $this->hasMany(CrmFollowUpTask::class, 'lead_id')
            ->where('tenant_id', TenantContext::currentId())
            ->orderByRaw("CASE WHEN due_at IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_at')
            ->orderByDesc('id');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return CrmLeadScope::applyVisibilityScope(
            $this->where($field ?? $this->getRouteKeyName(), $value)
        )->firstOrFail();
    }
}
