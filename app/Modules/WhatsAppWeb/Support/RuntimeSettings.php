<?php

namespace App\Modules\WhatsAppWeb\Support;

use App\Modules\WhatsAppWeb\Models\WhatsAppWebSetting;

class RuntimeSettings
{
    private static ?WhatsAppWebSetting $cachedWhatsAppSetting = null;
    private static bool $loaded = false;

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
        self::$loaded = false;
        self::$cachedWhatsAppSetting = null;
    }

    private static function setting(): ?WhatsAppWebSetting
    {
        if (!self::$loaded) {
            self::$cachedWhatsAppSetting = WhatsAppWebSetting::query()->first();
            self::$loaded = true;
        }

        return self::$cachedWhatsAppSetting;
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
