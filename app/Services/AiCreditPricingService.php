<?php

namespace App\Services;

use App\Models\AiCreditPricingSetting;
use Illuminate\Support\Facades\Schema;

class AiCreditPricingService
{
    private const PLATFORM_TENANT_ID = 1;

    public function snapshot(): array
    {
        $setting = $this->setting();
        $currency = strtoupper((string) ($setting?->currency ?: config('services.ai_credits.currency', 'IDR')));
        $unitTokens = max(1, (int) ($setting?->unit_tokens ?: config('services.ai_credits.unit_tokens', 1000)));
        $pricePerCredit = max(1, (int) ($setting?->price_per_credit ?: config('services.ai_credits.price_per_credit', 100)));
        $packOptions = $this->normalizePackOptions($setting?->pack_options ?? config('services.ai_credits.pack_options', [500, 1000]));

        return [
            'ready' => $this->ready(),
            'currency' => $currency,
            'unit_tokens' => $unitTokens,
            'price_per_credit' => $pricePerCredit,
            'pack_options' => $packOptions,
            'packs' => collect($packOptions)
                ->map(fn (int $credits) => [
                    'credits' => $credits,
                    'price' => $this->priceForCredits($credits, $pricePerCredit),
                ])
                ->values()
                ->all(),
        ];
    }

    public function unitTokens(): int
    {
        return (int) $this->snapshot()['unit_tokens'];
    }

    public function pricePerCredit(): int
    {
        return (int) $this->snapshot()['price_per_credit'];
    }

    public function currency(): string
    {
        return (string) $this->snapshot()['currency'];
    }

    public function packOptions(): array
    {
        return (array) $this->snapshot()['pack_options'];
    }

    public function priceForCredits(int $credits, ?int $pricePerCredit = null): int
    {
        return max(0, $credits) * max(1, (int) ($pricePerCredit ?? $this->pricePerCredit()));
    }

    public function ready(): bool
    {
        return Schema::hasTable('ai_credit_pricing_settings');
    }

    public function upsert(array $attributes): AiCreditPricingSetting
    {
        return AiCreditPricingSetting::query()->updateOrCreate(
            ['tenant_id' => self::PLATFORM_TENANT_ID],
            [
                'currency' => strtoupper((string) ($attributes['currency'] ?? 'IDR')),
                'unit_tokens' => max(1, (int) ($attributes['unit_tokens'] ?? 1000)),
                'price_per_credit' => max(1, (int) ($attributes['price_per_credit'] ?? 100)),
                'pack_options' => $this->normalizePackOptions($attributes['pack_options'] ?? [500, 1000]),
                'created_by' => $attributes['created_by'] ?? null,
                'updated_by' => $attributes['updated_by'] ?? null,
            ]
        );
    }

    private function setting(): ?AiCreditPricingSetting
    {
        if (!$this->ready()) {
            return null;
        }

        return AiCreditPricingSetting::query()
            ->where('tenant_id', self::PLATFORM_TENANT_ID)
            ->first();
    }

    private function normalizePackOptions(mixed $value): array
    {
        $packs = is_array($value)
            ? $value
            : explode(',', (string) $value);

        return collect($packs)
            ->map(fn ($pack) => (int) trim((string) $pack))
            ->filter(fn (int $pack) => $pack > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
