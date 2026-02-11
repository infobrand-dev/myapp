<?php

namespace App\Modules\SocialMedia\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'page_id',
        'ig_business_id',
        'access_token',
        'name',
        'status',
        'metadata',
        'created_by',
        'auto_reply',
        'chatbot_account_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'auto_reply' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function aiAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Chatbot\Models\ChatbotAccount::class, 'chatbot_account_id');
    }
}
