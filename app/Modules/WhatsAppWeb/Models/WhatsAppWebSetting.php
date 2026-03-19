<?php

namespace App\Modules\WhatsAppWeb\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppWebSetting extends Model
{
    protected $table = 'whatsapp_web_settings';
    protected $fillable = [
        'provider',
        'base_url',
        'verify_token',
        'default_sender_name',
        'is_active',
        'timeout_seconds',
        'notes',
        'created_by',
        'updated_by',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'timeout_seconds' => 'integer',
        'last_tested_at' => 'datetime',
    ];
}
