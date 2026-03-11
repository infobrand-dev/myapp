<?php

namespace App\Modules\WhatsAppBro\Support;

use App\Modules\WhatsAppBro\Models\WhatsAppBroSetting;

class RuntimeSettings
{
    private static ?WhatsAppBroSetting $cachedWhatsAppSetting = null;
    private static bool $loaded = false;

    public static function waBroBridgeUrl(): string
    {
        $setting = self::setting();

        return self::stringOrFallback(
            $setting ? $setting->base_url : null,
            (string) config('modules.whatsapp_bro.bridge_url', 'http://localhost:3020')
        );
    }

    public static function waBroWebhookToken(): ?string
    {
        $setting = self::setting();

        return self::nullable(
            ($setting ? $setting->verify_token : null) ?: config('modules.whatsapp_bro.webhook_token')
        );
    }

    public static function clearCache(): void
    {
        self::$loaded = false;
        self::$cachedWhatsAppSetting = null;
    }

    private static function setting(): ?WhatsAppBroSetting
    {
        if (!self::$loaded) {
            self::$cachedWhatsAppSetting = WhatsAppBroSetting::query()->first();
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
