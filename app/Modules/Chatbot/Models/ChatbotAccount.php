<?php

namespace App\Modules\Chatbot\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotAccount extends Model
{
    use HasFactory;

    protected $table = 'chatbot_accounts';

    protected $fillable = [
        'name',
        'provider',
        'model',
        'api_key',
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
}
