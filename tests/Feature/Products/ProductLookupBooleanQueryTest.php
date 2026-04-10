<?php

namespace Tests\Feature\Products;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Support\BooleanQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLookupBooleanQueryTest extends TestCase
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

    public function test_boolean_query_accepts_relation_instances_in_eager_load_callbacks(): void
    {
        $product = Product::query()->create([
            'tenant_id' => 1,
            'type' => 'simple',
            'name' => 'Produk Lookup',
            'slug' => 'produk-lookup',
            'sku' => 'PROD-LOOKUP',
            'cost_price' => 10000,
            'sell_price' => 15000,
            'is_active' => true,
            'track_stock' => true,
        ]);

        ProductVariant::query()->create([
            'tenant_id' => 1,
            'product_id' => $product->id,
            'name' => 'Variant Active',
            'sku' => 'PROD-LOOKUP-ACTIVE',
            'cost_price' => 10000,
            'sell_price' => 15500,
            'is_active' => true,
            'track_stock' => true,
            'position' => 1,
        ]);

        ProductVariant::query()->create([
            'tenant_id' => 1,
            'product_id' => $product->id,
            'name' => 'Variant Inactive',
            'sku' => 'PROD-LOOKUP-INACTIVE',
            'cost_price' => 10000,
            'sell_price' => 16000,
            'is_active' => false,
            'track_stock' => true,
            'position' => 2,
        ]);

        $loaded = Product::query()
            ->with([
                'variants' => fn ($query) => BooleanQuery::apply(
                    $query->whereNull('deleted_at')->orderBy('position'),
                    'is_active'
                ),
            ])
            ->findOrFail($product->id);

        $this->assertCount(1, $loaded->variants);
        $this->assertSame('Variant Active', $loaded->variants->first()->name);
    }
}
