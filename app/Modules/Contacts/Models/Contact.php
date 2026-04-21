<?php

namespace App\Modules\Contacts\Models;

use App\Models\Branch;
use App\Models\Company;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Contacts\Support\ContactPhoneNormalizer;
use App\Support\BooleanQuery;
use App\Support\NormalizesPgsqlBooleanAttributes;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'type',
        'parent_contact_id',
        'name',
        'job_title',
        'email',
        'phone',
        'mobile',
        'website',
        'vat',
        'tax_name',
        'company_registry',
        'industry',
        'payment_term_days',
        'credit_limit',
        'contact_person_name',
        'contact_person_phone',
        'street',
        'street2',
        'city',
        'state',
        'zip',
        'country',
        'billing_address',
        'shipping_address',
        'tax_address',
        'tax_is_pkp',
        'tags',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'payment_term_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'tax_is_pkp' => 'boolean',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return BooleanQuery::apply($query, 'is_active');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function parentContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'parent_contact_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Contact::class, 'parent_contact_id')
            ->where('tenant_id', TenantContext::currentId());
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = ContactPhoneNormalizer::normalize($value);
    }

    public function setMobileAttribute($value): void
    {
        $this->attributes['mobile'] = ContactPhoneNormalizer::normalize($value);
    }

    public function whatsappPhoneNumber(): ?string
    {
        return ContactPhoneNormalizer::normalize($this->mobile ?: $this->phone);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return ContactScope::applyVisibilityScope(
            $this->where($field ?? $this->getRouteKeyName(), $value)
        )
            ->firstOrFail();
    }
}
