<?php

namespace App\Modules\Discounts\Support\Engine;

class DiscountEvaluationResult
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $discountTotal,
        public readonly float $grandTotal,
        public readonly array $appliedDiscounts,
        public readonly array $rejectedDiscounts,
        public readonly array $lineTotals,
    ) {
    }

    public function toArray(): array
    {
        return [
            'subtotal' => round($this->subtotal, 2),
            'discount_total' => round($this->discountTotal, 2),
            'grand_total' => round($this->grandTotal, 2),
            'applied_discounts' => $this->appliedDiscounts,
            'rejected_discounts' => $this->rejectedDiscounts,
            'line_totals' => $this->lineTotals,
        ];
    }
}
