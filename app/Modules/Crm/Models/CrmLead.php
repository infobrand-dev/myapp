<?php

namespace App\Modules\Crm\Models;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\Support\CrmLeadScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLead extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'contact_id',
        'owner_user_id',
        'title',
        'stage',
        'priority',
        'lead_source',
        'estimated_value',
        'currency',
        'probability',
        'next_follow_up_at',
        'last_contacted_at',
        'won_at',
        'lost_at',
        'notes',
        'labels',
        'meta',
        'position',
        'is_archived',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'probability' => 'integer',
        'next_follow_up_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
        'labels' => 'array',
        'meta' => 'array',
        'is_archived' => 'boolean',
    ];

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

    public function resolveRouteBinding($value, $field = null)
    {
        return CrmLeadScope::applyVisibilityScope(
            $this->where($field ?? $this->getRouteKeyName(), $value)
        )->firstOrFail();
    }
}
