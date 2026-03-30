<?php

namespace App\Support;

use NumberFormatter;

class MoneyFormatter
{
    public function __construct(private readonly CurrencySettingsResolver $currencies)
    {
    }

    public function format(float|int|string|null $amount, ?string $currency = null, ?int $precision = null): string
    {
        $numeric = (float) ($amount ?? 0);
        $currency = strtoupper((string) ($currency ?: $this->currencies->defaultCurrency()));
        $precision ??= $currency === 'IDR' ? 0 : 2;

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($this->localeFor($currency), NumberFormatter::CURRENCY);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);

            $formatted = $formatter->formatCurrency($numeric, $currency);
            if ($formatted !== false) {
                return $formatted;
            }
        }

        return $this->symbolFor($currency) . number_format($numeric, $precision, ',', '.');
    }

    private function localeFor(string $currency): string
    {
        return match ($currency) {
            'USD' => 'en_US',
            'SGD' => 'en_SG',
            'EUR' => 'de_DE',
            default => 'id_ID',
        };
    }

    private function symbolFor(string $currency): string
    {
        return match ($currency) {
            'USD' => '$',
            'SGD' => 'S$',
            'EUR' => 'EUR ',
            default => 'Rp ',
        };
    }
}
