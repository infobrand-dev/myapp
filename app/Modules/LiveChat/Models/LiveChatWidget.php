<?php

namespace App\Modules\LiveChat\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LiveChatWidget extends Model
{
    use SoftDeletes;
    public const DEFAULT_TENANT_ID = 1;

    protected $fillable = [
        'tenant_id',
        'name',
        'website_name',
        'widget_token',
        'welcome_text',
        'theme_color',
        'launcher_label',
        'position',
        'logo_url',
        'header_bg_color',
        'visitor_bubble_color',
        'agent_bubble_color',
        'allowed_domains',
        'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'allowed_domains' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $widget): void {
            if (!$widget->tenant_id) {
                $widget->tenant_id = TenantContext::currentId();
            }

            if (blank($widget->widget_token)) {
                $widget->widget_token = Str::random(40);
            }
        });
    }

    public function embedScriptUrl(): string
    {
        return route('live-chat.widget.script', $this->widget_token);
    }

    public function embedCode(): string
    {
        return sprintf('<script src="%s" defer></script>', e($this->embedScriptUrl()));
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }
}
