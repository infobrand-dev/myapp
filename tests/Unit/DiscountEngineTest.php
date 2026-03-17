<?php

namespace Tests\Unit;

use App\Modules\Discounts\Models\Discount;
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
        $repository = Mockery::mock(DiscountRepository::class);
        $repository->shouldReceive('usageCountForDiscount')->andReturn(0);
        $repository->shouldReceive('usageCountForDiscountAndCustomer')->andReturn(0);

        $engine = new DiscountEngine($repository);

        $discount = new Discount([
            'id' => 99,
            'internal_name' => 'Promo 10%',
            'discount_type' => Discount::TYPE_PERCENTAGE,
            'application_scope' => Discount::SCOPE_ITEM,
            'priority' => 1,
            'sequence' => 1,
            'is_active' => true,
            'is_archived' => false,
            'stack_mode' => 'stackable',
            'combination_mode' => 'combinable',
            'rule_payload' => ['percentage' => 10],
        ]);
        $discount->setRelation('targets', new Collection());
        $discount->setRelation('conditions', new Collection());
        $discount->setRelation('vouchers', new Collection());

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
}
