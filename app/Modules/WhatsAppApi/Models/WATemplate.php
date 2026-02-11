<?php

namespace App\Modules\WhatsAppApi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WATemplate extends Model
{
    use HasFactory;

    protected $table = 'wa_templates';

    protected $fillable = [
        'name',
        'language',
        'category',
        'namespace',
        'body',
        'components',
        'status',
    ];

    protected $casts = [
        'components' => 'array',
    ];
}
