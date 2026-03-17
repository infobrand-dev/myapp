<?php

namespace App\Modules\Discounts\Support\Engine;

class DiscountEvaluationContext
{
    public function __construct(
        public readonly array $items,
        public readonly ?string $voucherCode,
        public readonly ?array $customer,
        public readonly ?string $outletReference,
        public readonly ?string $salesChannel,
        public readonly bool $manual,
        public readonly \DateTimeImmutable $now,
        public readonly array $meta = [],
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            items: array_values($payload['items'] ?? []),
            voucherCode: self::nullableString($payload['voucher_code'] ?? null),
            customer: self::normalizeCustomer($payload),
            outletReference: self::nullableString($payload['outlet_reference'] ?? ($payload['outlet_id'] ?? null)),
            salesChannel: self::nullableString($payload['sales_channel'] ?? null),
            manual: (bool) ($payload['manual'] ?? false),
            now: new \DateTimeImmutable((string) ($payload['at'] ?? 'now')),
            meta: $payload['meta'] ?? [],
        );
    }

    public function subtotal(): float
    {
        return (float) collect($this->items)->sum(fn (array $item) => (float) ($item['subtotal'] ?? 0));
    }

    public function customerReferenceType(): ?string
    {
        return $this->customer['reference_type'] ?? null;
    }

    public function customerReferenceId(): ?string
    {
        $value = $this->customer['reference_id'] ?? null;

        return $value === null ? null : (string) $value;
    }

    private static function normalizeCustomer(array $payload): ?array
    {
        $customer = $payload['customer'] ?? null;
        if (is_array($customer)) {
            return [
                'reference_type' => self::nullableString($customer['reference_type'] ?? $customer['type'] ?? null),
                'reference_id' => self::nullableString($customer['reference_id'] ?? $customer['id'] ?? null),
                'group_code' => self::nullableString($customer['group_code'] ?? null),
            ];
        }

        if (!empty($payload['customer_reference_id'])) {
            return [
                'reference_type' => self::nullableString($payload['customer_reference_type'] ?? 'contacts'),
                'reference_id' => self::nullableString($payload['customer_reference_id']),
                'group_code' => self::nullableString($payload['customer_group_code'] ?? null),
            ];
        }

        return null;
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
