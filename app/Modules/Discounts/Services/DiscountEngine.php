<?php

namespace App\Modules\Discounts\Services;

use App\Modules\Discounts\Models\Discount;
use App\Modules\Discounts\Models\DiscountCondition;
use App\Modules\Discounts\Models\DiscountTarget;
use App\Modules\Discounts\Models\DiscountVoucher;
use App\Modules\Discounts\Repositories\DiscountRepository;
use App\Modules\Discounts\Support\Engine\DiscountEvaluationContext;
use App\Modules\Discounts\Support\Engine\DiscountEvaluationResult;
use Illuminate\Support\Collection;

class DiscountEngine
{
    public function __construct(
        private readonly DiscountRepository $repository,
    ) {
    }

    public function evaluate(DiscountEvaluationContext $context, Collection $discounts): DiscountEvaluationResult
    {
        $lineTotals = collect($context->items)->mapWithKeys(function (array $item) {
            $lineKey = (string) $item['line_key'];

            return [$lineKey => [
                'line_key' => $lineKey,
                'product_id' => $item['product_id'] ?? null,
                'variant_id' => $item['variant_id'] ?? null,
                'product_name' => $item['product_name'] ?? null,
                'variant_name' => $item['variant_name'] ?? null,
                'quantity' => (float) ($item['quantity'] ?? 0),
                'subtotal_before' => round((float) ($item['subtotal'] ?? 0), 2),
                'discount_total' => 0.0,
                'total_after' => round((float) ($item['subtotal'] ?? 0), 2),
            ]];
        })->all();

        $subtotal = round($context->subtotal(), 2);
        $applied = [];
        $rejected = [];
        $cartLocked = false;
        $lineLocks = [];

        foreach ($discounts as $discount) {
            $voucher = $this->resolveVoucher($discount, $context->voucherCode);
            $eligibility = $this->validateEligibility($discount, $voucher, $context);
            if (!$eligibility['ok']) {
                $rejected[] = [
                    'discount_id' => $discount->id,
                    'internal_name' => $discount->internal_name,
                    'reason' => $eligibility['reason'],
                ];
                continue;
            }

            $eligibleLines = $this->eligibleLines($discount, $context);
            if (empty($eligibleLines)) {
                $rejected[] = [
                    'discount_id' => $discount->id,
                    'internal_name' => $discount->internal_name,
                    'reason' => 'Tidak ada target yang cocok.',
                ];
                continue;
            }

            if ($cartLocked && $discount->application_scope === Discount::SCOPE_INVOICE) {
                $rejected[] = [
                    'discount_id' => $discount->id,
                    'internal_name' => $discount->internal_name,
                    'reason' => 'Cart sudah terkunci oleh discount non-stackable sebelumnya.',
                ];
                continue;
            }

            $lineKeySet = collect($eligibleLines)->pluck('line_key')->all();
            $lockedLines = array_intersect($lineKeySet, array_keys(array_filter($lineLocks)));
            if (!empty($lockedLines) && $discount->application_scope === Discount::SCOPE_ITEM) {
                $rejected[] = [
                    'discount_id' => $discount->id,
                    'internal_name' => $discount->internal_name,
                    'reason' => 'Sebagian target line sudah terkunci oleh discount non-stackable sebelumnya.',
                ];
                continue;
            }

            $calculation = $this->calculate($discount, $eligibleLines, $lineTotals, $subtotal);
            if (($calculation['discount_amount'] ?? 0) <= 0) {
                $rejected[] = [
                    'discount_id' => $discount->id,
                    'internal_name' => $discount->internal_name,
                    'reason' => 'Rule cocok tetapi nilai discount bernilai 0.',
                ];
                continue;
            }

            foreach ($calculation['line_discounts'] as $lineKey => $amount) {
                $lineTotals[$lineKey]['discount_total'] = round($lineTotals[$lineKey]['discount_total'] + $amount, 2);
                $lineTotals[$lineKey]['total_after'] = max(0, round($lineTotals[$lineKey]['subtotal_before'] - $lineTotals[$lineKey]['discount_total'], 2));
            }

            $applied[] = [
                'discount_id' => $discount->id,
                'voucher_id' => $voucher?->id,
                'internal_name' => $discount->internal_name,
                'public_label' => $discount->public_label,
                'discount_type' => $discount->discount_type,
                'application_scope' => $discount->application_scope,
                'discount_amount' => round($calculation['discount_amount'], 2),
                'line_discounts' => $calculation['line_discounts'],
                'matched_line_keys' => $lineKeySet,
                'voucher_code' => $voucher?->code,
            ];

            if ($this->locksCart($discount)) {
                $cartLocked = true;
            }

            if ($this->locksLines($discount)) {
                foreach ($lineKeySet as $lineKey) {
                    $lineLocks[$lineKey] = true;
                }
            }
        }

        $discountTotal = round(collect($applied)->sum('discount_amount'), 2);
        $grandTotal = max(0, round($subtotal - $discountTotal, 2));

        return new DiscountEvaluationResult(
            subtotal: $subtotal,
            discountTotal: $discountTotal,
            grandTotal: $grandTotal,
            appliedDiscounts: $applied,
            rejectedDiscounts: $rejected,
            lineTotals: array_values($lineTotals),
        );
    }

    private function validateEligibility(Discount $discount, ?DiscountVoucher $voucher, DiscountEvaluationContext $context): array
    {
        if ($discount->is_manual_only && !$context->manual) {
            return ['ok' => false, 'reason' => 'Discount hanya boleh di-apply manual.'];
        }

        if ($discount->is_voucher_required && !$voucher) {
            return ['ok' => false, 'reason' => 'Voucher wajib untuk discount ini.'];
        }

        if ($context->voucherCode && $discount->is_voucher_required && !$voucher) {
            return ['ok' => false, 'reason' => 'Voucher tidak valid atau tidak cocok dengan discount.'];
        }

        if ($discount->usage_limit !== null && $this->repository->usageCountForDiscount($discount->id) >= $discount->usage_limit) {
            return ['ok' => false, 'reason' => 'Batas penggunaan discount sudah habis.'];
        }

        if (
            $discount->usage_limit_per_customer !== null
            && $this->repository->usageCountForDiscountAndCustomer(
                $discount->id,
                $context->customerReferenceType(),
                $context->customerReferenceId()
            ) >= $discount->usage_limit_per_customer
        ) {
            return ['ok' => false, 'reason' => 'Batas penggunaan discount per customer sudah habis.'];
        }

        if ($voucher) {
            if (!$voucher->is_active) {
                return ['ok' => false, 'reason' => 'Voucher tidak aktif.'];
            }

            $now = $context->now->format('Y-m-d H:i:s');
            if ($voucher->starts_at && $voucher->starts_at->format('Y-m-d H:i:s') > $now) {
                return ['ok' => false, 'reason' => 'Voucher belum aktif.'];
            }

            if ($voucher->ends_at && $voucher->ends_at->format('Y-m-d H:i:s') < $now) {
                return ['ok' => false, 'reason' => 'Voucher sudah expired.'];
            }

            if ($voucher->usage_limit !== null && $this->repository->usageCountForVoucher($voucher->id) >= $voucher->usage_limit) {
                return ['ok' => false, 'reason' => 'Batas penggunaan voucher sudah habis.'];
            }

            if (
                $voucher->usage_limit_per_customer !== null
                && $this->repository->usageCountForVoucherAndCustomer(
                    $voucher->id,
                    $context->customerReferenceType(),
                    $context->customerReferenceId()
                ) >= $voucher->usage_limit_per_customer
            ) {
                return ['ok' => false, 'reason' => 'Batas penggunaan voucher per customer sudah habis.'];
            }
        }

        foreach ($discount->conditions as $condition) {
            if (!$this->matchesCondition($condition, $discount, $context)) {
                return ['ok' => false, 'reason' => "Condition {$condition->condition_type} tidak terpenuhi."];
            }
        }

        return ['ok' => true, 'reason' => null];
    }

    private function resolveVoucher(Discount $discount, ?string $voucherCode): ?DiscountVoucher
    {
        if (!$voucherCode) {
            return null;
        }

        return $discount->vouchers->first(function (DiscountVoucher $voucher) use ($voucherCode) {
            return strcasecmp($voucher->code, $voucherCode) === 0;
        });
    }

    private function eligibleLines(Discount $discount, DiscountEvaluationContext $context): array
    {
        $lineTargets = $discount->targets->filter(fn (DiscountTarget $target) => in_array($target->target_type, ['all_products', 'product', 'variant', 'category', 'brand'], true));
        $contextTargets = $discount->targets->filter(fn (DiscountTarget $target) => in_array($target->target_type, ['customer', 'customer_group', 'outlet', 'sales_channel'], true));

        foreach ($contextTargets as $target) {
            if (!$this->matchesContextTarget($target, $context)) {
                return [];
            }
        }

        return collect($context->items)->filter(function (array $item) use ($lineTargets) {
            if ($lineTargets->isEmpty()) {
                return true;
            }

            $includeTargets = $lineTargets->where('operator', 'include');
            $excludeTargets = $lineTargets->where('operator', 'exclude');

            $included = $includeTargets->isEmpty() || $includeTargets->contains(fn (DiscountTarget $target) => $this->matchesLineTarget($target, $item));
            $excluded = $excludeTargets->contains(fn (DiscountTarget $target) => $this->matchesLineTarget($target, $item));

            return $included && !$excluded;
        })->values()->all();
    }

    private function matchesContextTarget(DiscountTarget $target, DiscountEvaluationContext $context): bool
    {
        return match ($target->target_type) {
            'customer' => (string) $target->target_id === (string) $context->customerReferenceId(),
            'customer_group' => (string) $target->target_code === (string) ($context->customer['group_code'] ?? null),
            'outlet' => (string) $target->target_code === (string) $context->outletReference,
            'sales_channel' => (string) $target->target_code === (string) $context->salesChannel,
            default => true,
        };
    }

    private function matchesLineTarget(DiscountTarget $target, array $item): bool
    {
        return match ($target->target_type) {
            'all_products' => true,
            'product' => (string) $target->target_id === (string) ($item['product_id'] ?? null),
            'variant' => (string) $target->target_id === (string) ($item['variant_id'] ?? null),
            'category' => (string) $target->target_id === (string) ($item['category_id'] ?? null),
            'brand' => (string) $target->target_id === (string) ($item['brand_id'] ?? null),
            default => false,
        };
    }

    private function matchesCondition(DiscountCondition $condition, Discount $discount, DiscountEvaluationContext $context): bool
    {
        $eligibleLines = $this->eligibleLines($discount, $context);
        $eligibleQty = (float) collect($eligibleLines)->sum('quantity');
        $eligibleSubtotal = (float) collect($eligibleLines)->sum('subtotal');
        $value = $condition->value;
        $payload = $condition->payload ?? [];
        $now = $context->now;

        return match ($condition->condition_type) {
            'minimum_quantity' => $eligibleQty >= (float) $value,
            'minimum_subtotal', 'minimum_transaction_amount' => $context->subtotal() >= (float) $value,
            'eligible_subtotal' => $eligibleSubtotal >= (float) $value,
            'buy_specific_product' => collect($context->items)->contains(fn (array $item) => (string) ($item['product_id'] ?? null) === (string) $value),
            'specific_customer' => (string) $context->customerReferenceId() === (string) $value,
            'date_range' => (!$payload['start'] || $now >= new \DateTimeImmutable($payload['start']))
                && (!$payload['end'] || $now <= new \DateTimeImmutable($payload['end'])),
            'day_of_week' => in_array((int) $now->format('N'), array_map('intval', (array) ($payload['days'] ?? [])), true),
            'time_range' => $this->matchesTimeRange($now, (string) ($payload['start'] ?? ''), (string) ($payload['end'] ?? '')),
            default => true,
        };
    }

    private function matchesTimeRange(\DateTimeImmutable $now, string $start, string $end): bool
    {
        if ($start === '' || $end === '') {
            return true;
        }

        $current = $now->format('H:i');
        return $current >= $start && $current <= $end;
    }

    private function calculate(Discount $discount, array $eligibleLines, array $lineTotals, float $cartSubtotal): array
    {
        return match ($discount->discount_type) {
            Discount::TYPE_PERCENTAGE => $this->calculatePercentage($discount, $eligibleLines, $lineTotals, $cartSubtotal),
            Discount::TYPE_FIXED_AMOUNT => $this->calculateFixedAmount($discount, $eligibleLines, $lineTotals, $cartSubtotal),
            Discount::TYPE_BUY_X_GET_Y => $this->calculateBuyXGetY($discount, $eligibleLines, $lineTotals),
            Discount::TYPE_FREE_ITEM => $this->calculateFreeItem($discount, $eligibleLines, $lineTotals),
            Discount::TYPE_BUNDLE => $this->calculateBundle($discount, $eligibleLines, $lineTotals),
            default => ['discount_amount' => 0, 'line_discounts' => []],
        };
    }

    private function calculatePercentage(Discount $discount, array $eligibleLines, array $lineTotals, float $cartSubtotal): array
    {
        $percentage = min(100, max(0, (float) data_get($discount->rule_payload, 'percentage', 0)));
        if ($percentage <= 0) {
            return ['discount_amount' => 0, 'line_discounts' => []];
        }

        if ($discount->application_scope === Discount::SCOPE_INVOICE) {
            $baseAmount = min($cartSubtotal, $this->remainingCartTotal($lineTotals));
            $discountAmount = round($baseAmount * ($percentage / 100), 2);
            return $this->distributeAcrossLines($discountAmount, $eligibleLines, $lineTotals, $discount);
        }

        $lineDiscounts = [];
        foreach ($eligibleLines as $item) {
            $remaining = $this->remainingLineTotal($lineTotals, $item['line_key']);
            $lineDiscounts[$item['line_key']] = round($remaining * ($percentage / 100), 2);
        }

        return $this->capDiscount($discount, $lineDiscounts);
    }

    private function calculateFixedAmount(Discount $discount, array $eligibleLines, array $lineTotals, float $cartSubtotal): array
    {
        $amount = max(0, (float) data_get($discount->rule_payload, 'amount', 0));
        if ($amount <= 0) {
            return ['discount_amount' => 0, 'line_discounts' => []];
        }

        $cap = $discount->application_scope === Discount::SCOPE_INVOICE
            ? min($amount, $this->remainingCartTotal($lineTotals), $cartSubtotal)
            : min($amount, (float) collect($eligibleLines)->sum(fn (array $item) => $this->remainingLineTotal($lineTotals, $item['line_key'])));

        return $this->distributeAcrossLines($cap, $eligibleLines, $lineTotals, $discount);
    }

    private function calculateBuyXGetY(Discount $discount, array $eligibleLines, array $lineTotals): array
    {
        $buyQty = max(1, (int) data_get($discount->rule_payload, 'buy_quantity', 1));
        $getQty = max(1, (int) data_get($discount->rule_payload, 'get_quantity', 1));
        $groups = intdiv((int) floor(collect($eligibleLines)->sum('quantity')), $buyQty + $getQty);
        if ($groups <= 0) {
            return ['discount_amount' => 0, 'line_discounts' => []];
        }

        $freeUnits = $groups * $getQty;
        $lineDiscounts = [];
        $sorted = collect($eligibleLines)->sortBy('unit_price')->values();

        foreach ($sorted as $item) {
            if ($freeUnits <= 0) {
                break;
            }

            $quantity = min((float) $item['quantity'], $freeUnits);
            $perUnit = (float) $item['unit_price'];
            $discountAmount = min($this->remainingLineTotal($lineTotals, $item['line_key']), round($quantity * $perUnit, 2));
            $lineDiscounts[$item['line_key']] = round(($lineDiscounts[$item['line_key']] ?? 0) + $discountAmount, 2);
            $freeUnits -= $quantity;
        }

        return $this->capDiscount($discount, $lineDiscounts);
    }

    private function calculateFreeItem(Discount $discount, array $eligibleLines, array $lineTotals): array
    {
        $freeQty = max(1, (int) data_get($discount->rule_payload, 'free_quantity', 1));
        $lineDiscounts = [];

        foreach (collect($eligibleLines)->sortBy('unit_price')->values() as $item) {
            if ($freeQty <= 0) {
                break;
            }

            $quantity = min((float) $item['quantity'], $freeQty);
            $discountAmount = min($this->remainingLineTotal($lineTotals, $item['line_key']), round($quantity * (float) $item['unit_price'], 2));
            $lineDiscounts[$item['line_key']] = round(($lineDiscounts[$item['line_key']] ?? 0) + $discountAmount, 2);
            $freeQty -= $quantity;
        }

        return $this->capDiscount($discount, $lineDiscounts);
    }

    private function calculateBundle(Discount $discount, array $eligibleLines, array $lineTotals): array
    {
        $minimumQty = max(1, (int) data_get($discount->rule_payload, 'minimum_bundle_quantity', 1));
        $eligibleQty = (float) collect($eligibleLines)->sum('quantity');
        if ($eligibleQty < $minimumQty) {
            return ['discount_amount' => 0, 'line_discounts' => []];
        }

        $mode = data_get($discount->rule_payload, 'bundle_discount_mode', 'percentage');
        if ($mode === 'fixed_amount') {
            return $this->calculateFixedAmount($discount, $eligibleLines, $lineTotals, $this->remainingCartTotal($lineTotals));
        }

        return $this->calculatePercentage($discount, $eligibleLines, $lineTotals, $this->remainingCartTotal($lineTotals));
    }

    private function capDiscount(Discount $discount, array $lineDiscounts): array
    {
        $lineDiscounts = collect($lineDiscounts)
            ->filter(fn ($amount) => $amount > 0)
            ->map(fn ($amount) => round((float) $amount, 2))
            ->all();

        $discountAmount = round(array_sum($lineDiscounts), 2);
        $cap = $discount->max_discount_amount ? (float) $discount->max_discount_amount : null;

        if ($cap !== null && $discountAmount > $cap && $discountAmount > 0) {
            $ratio = $cap / $discountAmount;
            $lineDiscounts = collect($lineDiscounts)->map(fn ($amount) => round($amount * $ratio, 2))->all();
            $discountAmount = round(array_sum($lineDiscounts), 2);
        }

        return [
            'discount_amount' => $discountAmount,
            'line_discounts' => $lineDiscounts,
        ];
    }

    private function distributeAcrossLines(float $discountAmount, array $eligibleLines, array $lineTotals, Discount $discount): array
    {
        if ($discountAmount <= 0 || empty($eligibleLines)) {
            return ['discount_amount' => 0, 'line_discounts' => []];
        }

        $remainingSubtotal = (float) collect($eligibleLines)->sum(fn (array $item) => $this->remainingLineTotal($lineTotals, $item['line_key']));
        if ($remainingSubtotal <= 0) {
            return ['discount_amount' => 0, 'line_discounts' => []];
        }

        $allocated = 0.0;
        $lineDiscounts = [];
        $lastIndex = count($eligibleLines) - 1;

        foreach (array_values($eligibleLines) as $index => $item) {
            $lineKey = $item['line_key'];
            $lineRemaining = $this->remainingLineTotal($lineTotals, $lineKey);
            if ($lineRemaining <= 0) {
                continue;
            }

            if ($index === $lastIndex) {
                $portion = round(min($lineRemaining, $discountAmount - $allocated), 2);
            } else {
                $portion = round(min($lineRemaining, $discountAmount * ($lineRemaining / $remainingSubtotal)), 2);
            }

            $lineDiscounts[$lineKey] = $portion;
            $allocated += $portion;
        }

        return $this->capDiscount($discount, $lineDiscounts);
    }

    private function remainingCartTotal(array $lineTotals): float
    {
        return (float) collect($lineTotals)->sum('total_after');
    }

    private function remainingLineTotal(array $lineTotals, string $lineKey): float
    {
        return max(0, (float) ($lineTotals[$lineKey]['total_after'] ?? 0));
    }

    private function locksCart(Discount $discount): bool
    {
        return $discount->application_scope === Discount::SCOPE_INVOICE
            && ($discount->stack_mode === 'non_stackable' || $discount->combination_mode === 'exclusive');
    }

    private function locksLines(Discount $discount): bool
    {
        return $discount->application_scope === Discount::SCOPE_ITEM
            && ($discount->stack_mode === 'non_stackable' || $discount->combination_mode === 'exclusive');
    }
}
