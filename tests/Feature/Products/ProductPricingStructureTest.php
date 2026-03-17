<?php

namespace Tests\Feature\Products;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductPrice;
use App\Modules\Products\Models\ProductPriceLevel;
use App\Modules\Products\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPricingStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Products/database/migrations',
            '--realpath' => false,
        ])->run();
    }

    public function test_base_tier_prices_are_persisted_in_product_prices_table(): void
    {
        $service = app(ProductService::class);

        $product = $service->create([
            'type' => 'simple',
            'name' => 'Produk A',
            'slug' => 'produk-a',
            'sku' => 'PROD-A',
            'cost_price' => 10000,
            'sell_price' => 15000,
            'wholesale_price' => 14000,
            'member_price' => 13000,
            'track_stock' => true,
            'is_active' => true,
        ]);

        $product = Product::query()->with('prices.priceLevel')->findOrFail($product->id);

        $this->assertSame(14000.0, $product->wholesale_price);
        $this->assertSame(13000.0, $product->member_price);
        $this->assertDatabaseCount('product_prices', 2);
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'product_variant_id' => null,
            'product_price_level_id' => ProductPriceLevel::query()->where('code', 'wholesale')->value('id'),
            'price' => 14000,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'product_variant_id' => null,
            'product_price_level_id' => ProductPriceLevel::query()->where('code', 'member')->value('id'),
            'price' => 13000,
        ]);
    }

    public function test_base_tier_price_update_replaces_previous_rows_for_same_level(): void
    {
        $service = app(ProductService::class);

        $product = Product::query()->create([
            'type' => 'simple',
            'name' => 'Produk B',
            'slug' => 'produk-b',
            'sku' => 'PROD-B',
            'cost_price' => 10000,
            'sell_price' => 20000,
            'is_active' => true,
            'track_stock' => true,
        ]);

        ProductPrice::query()->create([
            'product_id' => $product->id,
            'product_price_level_id' => ProductPriceLevel::query()->where('code', 'wholesale')->value('id'),
            'currency_code' => 'IDR',
            'price' => 18000,
            'minimum_qty' => 1,
            'is_active' => true,
        ]);

        $service->update($product, [
            'type' => 'simple',
            'name' => 'Produk B',
            'slug' => 'produk-b',
            'sku' => 'PROD-B',
            'cost_price' => 10000,
            'sell_price' => 20000,
            'wholesale_price' => 17000,
            'member_price' => null,
            'track_stock' => true,
            'is_active' => true,
        ]);

        $product->refresh()->load('prices.priceLevel');

        $this->assertSame(17000.0, $product->wholesale_price);
        $this->assertNull($product->member_price);
        $this->assertSame(1, $product->prices->where('priceLevel.code', 'wholesale')->count());
    }
}
