<?php

namespace Tests\Feature\Inventory;

use App\Models\Company;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Services\StockMutationService;
use App\Modules\Products\Models\Product;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReservationTest extends TestCase
{
    use RefreshDatabase;

    private static int $locationSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Products/database/migrations',
            '--realpath' => false,
        ])->run();

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Inventory/database/migrations',
            '--realpath' => false,
        ])->run();

        Company::query()->firstOrCreate(
            ['id' => 1],
            [
                'tenant_id' => 1,
                'name' => 'Default Company',
                'slug' => 'default-company',
                'code' => 'DEF',
                'is_active' => true,
            ]
        );

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId(1);
        BranchContext::setCurrentId(null);
    }

    public function test_reserve_release_and_consume_reserved_stock_updates_balances_safely(): void
    {
        $service = app(StockMutationService::class);
        [$product, $location] = $this->productAndLocation();

        $service->record([
            'product_id' => $product->id,
            'inventory_location_id' => $location->id,
            'movement_type' => 'opening_stock',
            'direction' => 'in',
            'quantity' => 10,
        ]);

        $service->reserve([
            'product_id' => $product->id,
            'inventory_location_id' => $location->id,
            'quantity' => 4,
            'reference_type' => 'sale',
            'reference_id' => 101,
        ]);

        $service->release([
            'product_id' => $product->id,
            'inventory_location_id' => $location->id,
            'quantity' => 1,
            'reference_type' => 'sale',
            'reference_id' => 101,
        ]);

        $service->consumeReserved([
            'product_id' => $product->id,
            'inventory_location_id' => $location->id,
            'quantity' => 3,
            'reference_type' => 'sale',
            'reference_id' => 101,
        ]);

        $this->assertDatabaseHas('inventory_stocks', [
            'product_id' => $product->id,
            'inventory_location_id' => $location->id,
            'current_quantity' => 7,
            'reserved_quantity' => 0,
        ]);
    }

    public function test_cannot_reserve_more_than_available_stock(): void
    {
        $service = app(StockMutationService::class);
        [$product, $location] = $this->productAndLocation();

        $service->record([
            'product_id' => $product->id,
            'inventory_location_id' => $location->id,
            'movement_type' => 'opening_stock',
            'direction' => 'in',
            'quantity' => 2,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Stok tersedia tidak cukup untuk reservasi.');

        $service->reserve([
            'product_id' => $product->id,
            'inventory_location_id' => $location->id,
            'quantity' => 3,
        ]);
    }

    public function test_cannot_consume_more_reserved_stock_than_current_hold(): void
    {
        $service = app(StockMutationService::class);
        [$product, $location] = $this->productAndLocation();

        $service->record([
            'product_id' => $product->id,
            'inventory_location_id' => $location->id,
            'movement_type' => 'opening_stock',
            'direction' => 'in',
            'quantity' => 5,
        ]);

        $service->reserve([
            'product_id' => $product->id,
            'inventory_location_id' => $location->id,
            'quantity' => 2,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Reserved quantity tidak cukup untuk diposting sebagai penjualan.');

        $service->consumeReserved([
            'product_id' => $product->id,
            'inventory_location_id' => $location->id,
            'quantity' => 3,
        ]);
    }

    private function productAndLocation(): array
    {
        self::$locationSequence++;

        $product = Product::query()->create([
            'type' => 'simple',
            'name' => 'Produk Reservasi',
            'slug' => 'produk-reservasi-' . self::$locationSequence,
            'sku' => 'RES-' . str_pad((string) self::$locationSequence, 3, '0', STR_PAD_LEFT),
            'cost_price' => 10000,
            'sell_price' => 15000,
            'is_active' => true,
            'track_stock' => true,
        ]);

        $location = InventoryLocation::query()->create([
            'code' => 'MAIN-' . self::$locationSequence,
            'name' => 'Gudang Utama ' . self::$locationSequence,
            'type' => 'warehouse',
            'is_default' => true,
            'is_active' => true,
        ]);

        return [$product, $location];
    }
}
