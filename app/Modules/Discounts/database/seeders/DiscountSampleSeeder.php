<?php

namespace App\Modules\Discounts\database\seeders;

use App\Modules\Discounts\Models\Discount;
use App\Modules\Discounts\Models\DiscountCondition;
use App\Modules\Discounts\Models\DiscountTarget;
use App\Modules\Discounts\Models\DiscountUsage;
use App\Modules\Discounts\Models\DiscountUsageLine;
use App\Modules\Discounts\Models\DiscountVoucher;
use App\Modules\Products\database\seeders\ProductSampleSeeder;
use App\Modules\Products\Models\Product;
use App\Support\SampleDataUserResolver;
use Illuminate\Database\Seeder;

class DiscountSampleSeeder extends Seeder
{
    public function run(): void
    {
        (new ProductSampleSeeder())->run();

        $user = SampleDataUserResolver::resolve();
        $userId = optional($user)->id;
        $product = Product::query()->where('sku', 'DEMO-COFFEE-250')->first();
        $productId = optional($product)->id;

        $discount = Discount::query()->updateOrCreate(
            ['code' => 'DISC-DEMO-10'],
            [
                'internal_name' => 'Diskon Demo 10%',
                'public_label' => 'Promo Demo 10%',
                'description' => 'Diskon sample untuk produk demo.',
                'discount_type' => Discount::TYPE_PERCENTAGE,
                'application_scope' => Discount::SCOPE_ITEM,
                'currency_code' => 'IDR',
                'priority' => 10,
                'sequence' => 10,
                'is_active' => true,
                'is_archived' => false,
                'is_voucher_required' => false,
                'is_manual_only' => false,
                'is_override_allowed' => true,
                'stack_mode' => 'stackable',
                'combination_mode' => 'combinable',
                'starts_at' => now()->subDays(3),
                'ends_at' => now()->addDays(30),
                'rule_payload' => ['type' => 'percentage', 'value' => 10],
                'meta' => ['seeded' => true],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        if ($product) {
            DiscountTarget::query()->updateOrCreate(
                [
                    'discount_id' => $discount->id,
                    'target_type' => 'product',
                    'target_id' => $product->id,
                ],
                [
                    'target_code' => $product->sku,
                    'operator' => 'include',
                    'sort_order' => 1,
                    'payload' => ['seeded' => true],
                ]
            );
        }

        DiscountCondition::query()->updateOrCreate(
            [
                'discount_id' => $discount->id,
                'condition_type' => 'min_qty',
                'sort_order' => 1,
            ],
            [
                'operator' => '>=',
                'value_type' => 'number',
                'value' => '1',
                'secondary_value' => null,
                'payload' => ['seeded' => true],
            ]
        );

        $voucher = DiscountVoucher::query()->updateOrCreate(
            ['code' => 'VOUCHER-DEMO'],
            [
                'discount_id' => $discount->id,
                'description' => 'Voucher demo untuk pengujian UI.',
                'starts_at' => now()->subDays(3),
                'ends_at' => now()->addDays(30),
                'usage_limit' => 100,
                'usage_limit_per_customer' => 1,
                'is_active' => true,
                'meta' => ['seeded' => true],
            ]
        );

        $usage = DiscountUsage::query()->updateOrCreate(
            [
                'discount_id' => $discount->id,
                'usage_reference_type' => 'sale',
                'usage_reference_id' => 'SALE-DEMO-001',
            ],
            [
                'voucher_id' => $voucher->id,
                'customer_reference_type' => 'contact',
                'customer_reference_id' => 'procurement@demo-nusantara.test',
                'outlet_reference' => 'MAIN',
                'sales_channel' => 'backoffice',
                'usage_status' => 'applied',
                'currency_code' => 'IDR',
                'subtotal_before' => 65000,
                'discount_total' => 6500,
                'grand_total_after' => 58500,
                'evaluated_at' => now()->subDay(),
                'applied_at' => now()->subDay(),
                'snapshot' => ['seeded' => true],
                'meta' => ['seeded' => true],
            ]
        );

        DiscountUsageLine::query()->updateOrCreate(
            [
                'discount_usage_id' => $usage->id,
                'discount_id' => $discount->id,
                'line_key' => 'line-1',
            ],
            [
                'voucher_id' => $voucher->id,
                'product_id' => $productId,
                'variant_id' => null,
                'quantity' => 1,
                'subtotal_before' => 65000,
                'discount_amount' => 6500,
                'total_after' => 58500,
                'snapshot' => ['seeded' => true],
            ]
        );
    }
}


