<?php

namespace App\Modules\WhatsAppWeb\Support;

use App\Modules\WhatsAppWeb\Models\WhatsAppWebSetting;
use App\Support\TenantContext;

class RuntimeSettings
{
    /**
     * @var array<int, WhatsAppWebSetting|null>
     */
    private static array $cachedWhatsAppSettings = [];

    public static function waWebBridgeUrl(): string
    {
        $setting = self::setting();

        return self::stringOrFallback(
            $setting ? $setting->base_url : null,
            (string) config('modules.whatsapp_web.bridge_url', config('modules.whatsapp_bro.bridge_url', 'http://localhost:3020'))
        );
    }

    public static function waWebWebhookToken(): ?string
    {
        $setting = self::setting();

        return self::nullable(
            ($setting ? $setting->verify_token : null) ?: config('modules.whatsapp_web.webhook_token', config('modules.whatsapp_bro.webhook_token'))
        );
    }

    public static function clearCache(): void
    {
        self::$cachedWhatsAppSettings = [];
    }

    private static function setting(): ?WhatsAppWebSetting
    {
        $tenantId = TenantContext::currentId();

        if (!array_key_exists($tenantId, self::$cachedWhatsAppSettings)) {
            self::$cachedWhatsAppSettings[$tenantId] = WhatsAppWebSetting::query()
                ->where('tenant_id', TenantContext::currentId())
                ->first();
        }

        return self::$cachedWhatsAppSettings[$tenantId];
    }

    private static function stringOrFallback(?string $value, string $fallback): string
    {
        $trimmed = trim((string) $value);
        return $trimmed !== '' ? $trimmed : $fallback;
    }

    private static function nullable(mixed $value): ?string
    {
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }
}
