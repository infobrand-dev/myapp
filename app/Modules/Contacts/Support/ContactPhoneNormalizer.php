<?php

namespace App\Modules\Contacts\Support;

class ContactPhoneNormalizer
{
    public static function normalize(?string $value, string $defaultCountryCode = '62'): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d\+]+/', '', $raw) ?? '';
        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, '+')) {
            $normalized = substr($normalized, 1);
        } elseif (str_starts_with($normalized, '00')) {
            $normalized = substr($normalized, 2);
        }

        $digits = preg_replace('/\D+/', '', $normalized) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = $defaultCountryCode . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = $defaultCountryCode . $digits;
        }

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
