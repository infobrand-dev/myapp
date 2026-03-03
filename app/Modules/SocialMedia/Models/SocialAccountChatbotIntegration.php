<?php

namespace App\Modules\SocialMedia\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccountChatbotIntegration extends Model
{
    use HasFactory;

    protected $table = 'social_account_chatbot_integrations';

    protected $fillable = [
        'social_account_id',
        'auto_reply',
        'chatbot_account_id',
        'settings',
    ];

    protected $casts = [
        'auto_reply' => 'boolean',
        'settings' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }
}

