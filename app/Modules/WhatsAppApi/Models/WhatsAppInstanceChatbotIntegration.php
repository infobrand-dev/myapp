<?php

namespace App\Modules\WhatsAppApi\Models;

use App\Support\NormalizesPgsqlBooleanAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppInstanceChatbotIntegration extends Model
{
    use HasFactory;
    use NormalizesPgsqlBooleanAttributes;

    protected $table = 'whatsapp_instance_chatbot_integrations';

    protected $fillable = [
        'instance_id',
        'auto_reply',
        'chatbot_account_id',
        'settings',
    ];

    protected $casts = [
        'auto_reply' => 'boolean',
        'settings' => 'array',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsAppInstance::class, 'instance_id');
    }
}
