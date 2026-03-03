<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'provider',
        'version',
        'is_active',
        'installed_at',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'installed_at' => 'datetime',
        'meta' => 'array',
    ];
}

