<?php

namespace App\Modules\WhatsAppApi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WAContactPhoneStatus extends Model
{
    use HasFactory;

    protected $table = 'wa_contact_phone_statuses';

    protected $fillable = [
        'phone_number',
        'last_contact_name',
        'status',
        'failure_count',
        'last_error',
        'last_failed_at',
    ];

    protected $casts = [
        'last_failed_at' => 'datetime',
    ];
}
