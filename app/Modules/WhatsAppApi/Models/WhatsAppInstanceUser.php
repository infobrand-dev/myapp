<?php

namespace App\Modules\WhatsAppApi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppInstanceUser extends Model
{
    protected $table = 'whatsapp_instance_user';

    protected $fillable = [
        'instance_id',
        'user_id',
        'role',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'instance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
