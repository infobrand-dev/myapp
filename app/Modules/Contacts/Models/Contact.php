<?php

namespace App\Modules\Contacts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
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
        return $this->belongsTo(Contact::class, 'company_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Contact::class, 'company_id');
    }
}
