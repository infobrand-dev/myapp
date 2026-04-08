<?php

namespace App\Models;

use App\Support\NormalizesPgsqlBooleanAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCompany extends Model
{
    use NormalizesPgsqlBooleanAttributes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'company_id',
        'is_default',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'company_id' => 'integer',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
