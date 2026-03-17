<?php

namespace Tests\Unit;

use App\Modules\Discounts\Models\Discount;
use App\Modules\Discounts\Models\DiscountTarget;
use App\Modules\Discounts\Models\DiscountVoucher;
use App\Modules\Discounts\Repositories\DiscountRepository;
use App\Modules\Discounts\Services\DiscountEngine;
use App\Modules\Discounts\Support\Engine\DiscountEvaluationContext;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class DiscountEngineTest extends TestCase
{
    public function test_percentage_discount_is_applied_to_matching_line_items(): void
    {
        $engine = new DiscountEngine($this->mockRepository());

        $discount = $this->makeDiscount([
            'id' => 99,
            'internal_name' => 'Promo 10%',
            'discount_type' => Discount::TYPE_PERCENTAGE,
            'application_scope' => Discount::SCOPE_ITEM,
            'rule_payload' => ['percentage' => 10],
        ]);

        $context = DiscountEvaluationContext::fromArray([
            'items' => [
                ['line_key' => 'line-1', 'product_id' => 1, 'quantity' => 2, 'unit_price' => 50000, 'subtotal' => 100000],
            ],
        ]);

        $result = $engine->evaluate($context, collect([$discount]));

        $this->assertSame(100000.0, $result->subtotal);
        $this->assertSame(10000.0, $result->discountTotal);
        $this->assertSame(90000.0, $result->grandTotal);
        $this->assertCount(1, $result->appliedDiscounts);
    }

    public function test_non_stackable_invoice_discount_locks_cart_from_later_invoice_discount(): void
    {
        $engine = new DiscountEngine($this->mockRepository());

        $exclusive = $this->makeDiscount([
            'id' => 1,
            'internal_name' => 'Voucher Exclusive',
            'discount_type' => Discount::TYPE_FIXED_AMOUNT,
            'application_scope' => Discount::SCOPE_INVOICE,
            'stack_mode' => 'non_stackable',
            'combination_mode' => 'exclusive',
            'rule_payload' => ['amount' => 30000],
        ]);

        $combinable = $this->makeDiscount([
            'id' => 2,
            'internal_name' => 'Cashback 10%',
            'discount_type' => Discount::TYPE_PERCENTAGE,
            'application_scope' => Discount::SCOPE_INVOICE,
            'rule_payload' => ['percentage' => 10],
        ]);

        $context = DiscountEvaluationContext::fromArray([
            'items' => [
                ['line_key' => 'cart-1', 'product_id' => 10, 'quantity' => 1, 'unit_price' => 150000, 'subtotal' => 150000],
            ],
        ]);

        $result = $engine->evaluate($context, collect([$exclusive, $combinable]));

        $this->assertSame(30000.0, $result->discountTotal);
        $this->assertCount(1, $result->appliedDiscounts);
        $this->assertSame('Voucher Exclusive', $result->appliedDiscounts[0]['internal_name']);
        $this->assertCount(1, $result->rejectedDiscounts);
        $this->assertSame('Cashback 10%', $result->rejectedDiscounts[0]['internal_name']);
    }

    public function test_item_level_non_stackable_discount_only_locks_matching_lines(): void
    {
        $engine = new DiscountEngine($this->mockRepository());

        $productOneExclusive = $this->makeDiscount([
            'id' => 1,
            'internal_name' => 'P1 Exclusive',
            'discount_type' => Discount::TYPE_PERCENTAGE,
            'application_scope' => Discount::SCOPE_ITEM,
            'stack_mode' => 'non_stackable',
            'combination_mode' => 'exclusive',
            'rule_payload' => ['percentage' => 50],
        ], targets: [
            new DiscountTarget(['target_type' => 'product', 'target_id' => 1, 'operator' => 'include']),
        ]);

        $productTwoCombinable = $this->makeDiscount([
            'id' => 2,
            'internal_name' => 'P2 Combinable',
            'discount_type' => Discount::TYPE_PERCENTAGE,
            'application_scope' => Discount::SCOPE_ITEM,
            'rule_payload' => ['percentage' => 10],
        ], targets: [
            new DiscountTarget(['target_type' => 'product', 'target_id' => 2, 'operator' => 'include']),
        ]);

        $context = DiscountEvaluationContext::fromArray([
            'items' => [
                ['line_key' => 'line-1', 'product_id' => 1, 'quantity' => 1, 'unit_price' => 100000, 'subtotal' => 100000],
                ['line_key' => 'line-2', 'product_id' => 2, 'quantity' => 1, 'unit_price' => 50000, 'subtotal' => 50000],
            ],
        ]);

        $result = $engine->evaluate($context, collect([$productOneExclusive, $productTwoCombinable]));

        $this->assertSame(55000.0, $result->discountTotal);
        $this->assertCount(2, $result->appliedDiscounts);
        $this->assertCount(0, $result->rejectedDiscounts);
    }

    public function test_voucher_usage_limit_is_enforced(): void
    {
        $engine = new DiscountEngine($this->mockRepository([
            'usageCountForVoucher' => 1,
        ]));

        $discount = $this->makeDiscount([
            'id' => 10,
            'internal_name' => 'Voucher PROMO',
            'discount_type' => Discount::TYPE_FIXED_AMOUNT,
            'application_scope' => Discount::SCOPE_INVOICE,
            'is_voucher_required' => true,
            'rule_payload' => ['amount' => 20000],
        ], vouchers: [$this->makeVoucher([
            'id' => 100,
            'code' => 'PROMO',
            'usage_limit' => 1,
            'is_active' => true,
        ])]);

        $context = DiscountEvaluationContext::fromArray([
            'voucher_code' => 'PROMO',
            'items' => [
                ['line_key' => 'line-1', 'product_id' => 1, 'quantity' => 1, 'unit_price' => 100000, 'subtotal' => 100000],
            ],
        ]);

        $result = $engine->evaluate($context, collect([$discount]));

        $this->assertSame(0.0, $result->discountTotal);
        $this->assertCount(0, $result->appliedDiscounts);
        $this->assertCount(1, $result->rejectedDiscounts);
        $this->assertSame('Batas penggunaan voucher sudah habis.', $result->rejectedDiscounts[0]['reason']);
    }

    public function test_multiple_combinable_discounts_follow_evaluation_order_and_remaining_total(): void
    {
        $engine = new DiscountEngine($this->mockRepository());

        $priorityOne = $this->makeDiscount([
            'id' => 1,
            'internal_name' => 'Half Price',
            'discount_type' => Discount::TYPE_PERCENTAGE,
            'application_scope' => Discount::SCOPE_INVOICE,
            'priority' => 1,
            'rule_payload' => ['percentage' => 50],
        ]);

        $priorityTwo = $this->makeDiscount([
            'id' => 2,
            'internal_name' => 'Minus 40K',
            'discount_type' => Discount::TYPE_FIXED_AMOUNT,
            'application_scope' => Discount::SCOPE_INVOICE,
            'priority' => 2,
            'rule_payload' => ['amount' => 40000],
        ]);

        $context = DiscountEvaluationContext::fromArray([
            'items' => [
                ['line_key' => 'line-1', 'product_id' => 1, 'quantity' => 1, 'unit_price' => 100000, 'subtotal' => 100000],
            ],
        ]);

        $result = $engine->evaluate($context, collect([$priorityOne, $priorityTwo]));

        $this->assertSame(90000.0, $result->discountTotal);
        $this->assertSame(10000.0, $result->grandTotal);
        $this->assertSame('Half Price', $result->appliedDiscounts[0]['internal_name']);
        $this->assertSame('Minus 40K', $result->appliedDiscounts[1]['internal_name']);
    }

    public function test_buy_x_get_y_can_consume_mixed_line_items_and_discount_cheapest_units(): void
    {
        $engine = new DiscountEngine($this->mockRepository());

        $discount = $this->makeDiscount([
            'id' => 20,
            'internal_name' => 'Buy 2 Get 1',
            'discount_type' => Discount::TYPE_BUY_X_GET_Y,
            'application_scope' => Discount::SCOPE_ITEM,
            'rule_payload' => [
                'buy_quantity' => 2,
                'get_quantity' => 1,
            ],
        ], targets: [
            new DiscountTarget(['target_type' => 'all_products', 'operator' => 'include']),
        ]);

        $context = DiscountEvaluationContext::fromArray([
            'items' => [
                ['line_key' => 'expensive', 'product_id' => 1, 'quantity' => 2, 'unit_price' => 100000, 'subtotal' => 200000],
                ['line_key' => 'cheap', 'product_id' => 2, 'quantity' => 1, 'unit_price' => 50000, 'subtotal' => 50000],
            ],
        ]);

        $result = $engine->evaluate($context, collect([$discount]));

        $this->assertSame(50000.0, $result->discountTotal);
        $this->assertSame(['cheap' => 50000.0], $result->appliedDiscounts[0]['line_discounts']);
    }

    private function mockRepository(array $overrides = []): DiscountRepository
    {
        $repository = Mockery::mock(DiscountRepository::class);

        $repository->shouldReceive('usageCountForDiscount')->andReturn($overrides['usageCountForDiscount'] ?? 0);
        $repository->shouldReceive('usageCountForDiscountAndCustomer')->andReturn($overrides['usageCountForDiscountAndCustomer'] ?? 0);
        $repository->shouldReceive('usageCountForVoucher')->andReturn($overrides['usageCountForVoucher'] ?? 0);
        $repository->shouldReceive('usageCountForVoucherAndCustomer')->andReturn($overrides['usageCountForVoucherAndCustomer'] ?? 0);

        return $repository;
    }

    private function makeDiscount(array $attributes, array $targets = [], array $conditions = [], array $vouchers = []): Discount
    {
        $discount = new Discount(array_merge([
            'priority' => 1,
            'sequence' => 1,
            'is_active' => true,
            'is_archived' => false,
            'stack_mode' => 'stackable',
            'combination_mode' => 'combinable',
        ], $attributes));

        $discount->setRelation('targets', new Collection($targets));
        $discount->setRelation('conditions', new Collection($conditions));
        $discount->setRelation('vouchers', new Collection($vouchers));

        return $discount;
    }

    private function makeVoucher(array $attributes): DiscountVoucher
    {
        $voucher = new DiscountVoucher();
        $voucher->forceFill($attributes);

        return $voucher;
    }
}
