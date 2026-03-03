<?php

namespace App\Modules\SocialMedia\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function chatbotIntegration(): HasOne
    {
        return $this->hasOne(SocialAccountChatbotIntegration::class, 'social_account_id');
    }
}
