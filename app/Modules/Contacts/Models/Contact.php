<?php

namespace App\Modules\Contacts\Models;

use App\Modules\Contacts\Support\ContactPhoneNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
        'tenant_id',
        'type',
        'company_id',
        'name',
        'job_title',
        'email',
        'phone',
        'mobile',
        'website',
        'vat',
        'company_registry',
        'industry',
        'street',
        'street2',
        'city',
        'state',
        'zip',
        'country',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'company_id')
            ->where('tenant_id', 1);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Contact::class, 'company_id')
            ->where('tenant_id', 1);
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
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', 1)
            ->firstOrFail();
    }
}
