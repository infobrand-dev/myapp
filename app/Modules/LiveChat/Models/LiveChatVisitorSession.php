<?php

namespace App\Modules\LiveChat\Models;

use Illuminate\Database\Eloquent\Model;

class LiveChatVisitorSession extends Model
{
    public ?string $session_token_plain = null;

    protected $fillable = [
        'tenant_id',
        'live_chat_widget_id',
        'conversation_id',
        'visitor_key',
        'session_token_hash',
        'origin_host',
        'ip_address',
        'user_agent',
        'last_seen_at',
        'expires_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'last_seen_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $session): void {
            if (!$session->tenant_id) {
                $session->tenant_id = LiveChatWidget::DEFAULT_TENANT_ID;
            }
        });
    }
}
