<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SaleIdempotencyService
{
    public function hashFromPayload(array $data): string
    {
        return $this->hash($this->canonicalPayload($data));
    }

    public function hashFromSale(Sale $sale): string
    {
        $sale->loadMissing('items');

        return $this->hash([
            'source' => (string) $sale->source,
            'external_reference' => $this->nullableString($sale->external_reference),
            'contact_id' => $sale->contact_id ? (int) $sale->contact_id : null,
            'payment_status' => (string) $sale->payment_status,
            'transaction_date' => $this->normalizeDateTime(optional($sale->transaction_date)->toDateTimeString()),
            'currency_code' => strtoupper((string) $sale->currency_code),
            'tax_rate_id' => data_get($sale->meta, 'tax.tax_rate_id') ? (int) data_get($sale->meta, 'tax.tax_rate_id') : null,
            'header_discount_total' => $this->normalizeNumber(data_get($sale->totals_snapshot, 'header_discount_total', 0), 2),
            'header_tax_total' => $this->normalizeNumber(data_get($sale->totals_snapshot, 'header_tax_total', 0), 2),
            'notes' => $this->nullableString($sale->notes),
            'customer_note' => $this->nullableString($sale->customer_note),
            'items' => $sale->items
                ->sortBy('line_no')
                ->values()
                ->map(fn ($item) => [
                    'product_id' => $item->product_id ? (int) $item->product_id : null,
                    'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                    'qty' => $this->normalizeNumber($item->qty, 4),
                    'unit_price' => $this->normalizeNumber($item->unit_price, 2),
                    'discount_total' => $this->normalizeNumber($item->discount_total, 2),
                    'tax_total' => $this->normalizeNumber($item->tax_total, 2),
                    'notes' => $this->nullableString($item->notes),
                ])
                ->all(),
        ]);
    }

    public function assertMatches(Sale $sale, array $data): void
    {
        $incomingHash = $this->hashFromPayload($data);
        $existingHash = $sale->idempotency_payload_hash ?: $this->hashFromSale($sale);

        if (!hash_equals($existingHash, $incomingHash)) {
            throw ValidationException::withMessages([
                'external_reference' => 'External reference ini sudah dipakai untuk payload transaksi yang berbeda.',
            ]);
        }
    }

    public function mergeMeta(array $meta, array $data): array
    {
        return $meta;
    }

    private function canonicalPayload(array $data): array
    {
        return [
            'source' => (string) ($data['source'] ?? ''),
            'external_reference' => $this->nullableString($data['external_reference'] ?? null),
            'contact_id' => !empty($data['contact_id']) ? (int) $data['contact_id'] : null,
            'payment_status' => (string) ($data['payment_status'] ?? ''),
            'transaction_date' => $this->normalizeDateTime($data['transaction_date'] ?? null),
            'currency_code' => strtoupper((string) ($data['currency_code'] ?? 'IDR')),
            'tax_rate_id' => !empty($data['tax_rate_id']) ? (int) $data['tax_rate_id'] : null,
            'header_discount_total' => $this->normalizeNumber($data['header_discount_total'] ?? 0, 2),
            'header_tax_total' => $this->normalizeNumber($data['header_tax_total'] ?? 0, 2),
            'notes' => $this->nullableString($data['notes'] ?? null),
            'customer_note' => $this->nullableString($data['customer_note'] ?? null),
            'items' => collect($data['items'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->values()
                ->map(fn (array $item) => [
                    'product_id' => !empty($item['product_id']) ? (int) $item['product_id'] : null,
                    'product_variant_id' => !empty($item['product_variant_id']) ? (int) $item['product_variant_id'] : null,
                    'qty' => $this->normalizeNumber($item['qty'] ?? 0, 4),
                    'unit_price' => $this->normalizeNumber($item['unit_price'] ?? 0, 2),
                    'discount_total' => $this->normalizeNumber($item['discount_total'] ?? 0, 2),
                    'tax_total' => $this->normalizeNumber($item['tax_total'] ?? 0, 2),
                    'notes' => $this->nullableString($item['notes'] ?? null),
                ])
                ->all(),
        ];
    }

    private function hash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function normalizeDateTime(null|string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value)->utc()->toDateTimeString();
    }

    private function normalizeNumber(mixed $value, int $precision): string
    {
        return number_format((float) $value, $precision, '.', '');
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
