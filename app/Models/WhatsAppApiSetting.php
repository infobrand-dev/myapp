<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppApiSetting extends Model
{
    protected $fillable = [
        'provider',
        'base_url',
        'phone_number_id',
        'waba_id',
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
        'access_token' => 'encrypted',
        'is_active' => 'boolean',
        'timeout_seconds' => 'integer',
        'last_tested_at' => 'datetime',
    ];
}
