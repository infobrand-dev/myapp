<?php

namespace App\Modules\Crm\Models;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'contact_id',
        'lead_id',
        'owner_user_id',
        'activity_type',
        'source_suite',
        'source_module',
        'title',
        'description',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id')->where('tenant_id', TenantContext::currentId());
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id')->where('tenant_id', TenantContext::currentId());
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
}
