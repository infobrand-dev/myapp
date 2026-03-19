<?php

namespace App\Modules\Products\Database\Seeders;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductBrand;
use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Models\ProductPrice;
use App\Modules\Products\Models\ProductUnit;
use App\Support\SampleDataUserResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSampleSeeder extends Seeder
{
    public function run(): void
    {
        $user = SampleDataUserResolver::resolve();
        $userId = optional($user)->id;

        $category = ProductCategory::query()->updateOrCreate(
            ['slug' => 'minuman-demo'],
            [
                'name' => 'Minuman Demo',
                'description' => 'Kategori sample data.',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $brand = ProductBrand::query()->updateOrCreate(
            ['slug' => 'demo-brand'],
            [
                'name' => 'Demo Brand',
                'description' => 'Brand sample data.',
                'is_active' => true,
            ]
        );

        $unit = ProductUnit::query()->updateOrCreate(
            ['code' => 'PCS'],
            [
                'name' => 'Pieces',
                'description' => 'Unit default sample.',
                'precision' => 0,
                'is_active' => true,
            ]
        );

        $product = Product::query()->updateOrCreate(
            ['sku' => 'DEMO-COFFEE-250'],
            [
                'type' => 'simple',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'unit_id' => $unit->id,
                'name' => 'Demo Coffee Beans 250gr',
                'slug' => 'demo-coffee-beans-250gr',
                'barcode' => '8999000000010',
                'description' => 'Produk sample untuk modul Products, Inventory, Discounts, dan Sales.',
                'cost_price' => 45000,
                'sell_price' => 65000,
                'is_active' => true,
                'track_stock' => true,
                'meta' => ['seeded' => true],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        $priceLevels = DB::table('product_price_levels')
            ->whereIn('code', ['wholesale', 'member'])
            ->pluck('id', 'code');

        foreach (['wholesale' => 62000, 'member' => 60000] as $code => $price) {
            $levelId = $priceLevels[$code] ?? null;
            if (!$levelId) {
                continue;
            }

            ProductPrice::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'product_price_level_id' => $levelId,
                    'minimum_qty' => 1,
                ],
                [
                    'currency_code' => 'IDR',
                    'price' => $price,
                    'is_active' => true,
                    'meta' => ['seeded' => true],
                ]
            );
        }
    }
}
