<?php

namespace App\Support;

use App\Models\WhatsAppApiSetting;

class ModuleRuntimeSettings
{
    private static ?WhatsAppApiSetting $cachedWhatsAppSetting = null;
    private static bool $loaded = false;

    public static function waBroBridgeUrl(): string
    {
        return self::stringOrFallback(
            self::setting()?->base_url,
            (string) config('modules.whatsapp_bro.bridge_url', 'http://localhost:3020')
        );
    }

    public static function waBroWebhookToken(): ?string
    {
        return self::nullable(
            self::setting()?->verify_token ?: config('modules.whatsapp_bro.webhook_token')
        );
    }

    public static function clearCache(): void
    {
        self::$loaded = false;
        self::$cachedWhatsAppSetting = null;
    }

    private static function setting(): ?WhatsAppApiSetting
    {
        if (!self::$loaded) {
            self::$cachedWhatsAppSetting = WhatsAppApiSetting::query()->first();
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
