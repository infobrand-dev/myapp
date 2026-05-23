<?php

namespace Tests\Feature\Inventory;

use App\Models\Company;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\StockBalance;
use App\Modules\Inventory\Services\StockMutationService;
use App\Modules\Products\Models\Product;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\TestCase;

class InventoryMovingAverageAuditTest extends TestCase
{
    use BootstrapsModuleContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('name');
            $table->string('slug');
            $table->string('sku');
            $table->string('type', 50)->default('physical');
            $table->decimal('cost_price', 18, 2)->default(0);
            $table->decimal('sell_price', 18, 2)->default(0);
            $table->boolean('track_stock')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('cost_price', 18, 2)->default(0);
            $table->decimal('sell_price', 18, 2)->default(0);
            $table->boolean('track_stock')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('code');
            $table->string('name');
            $table->string('type', 50)->default('warehouse');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('stock_key');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $table->decimal('current_quantity', 18, 4)->default(0);
            $table->decimal('reserved_quantity', 18, 4)->default(0);
            $table->decimal('minimum_quantity', 18, 4)->default(0);
            $table->decimal('reorder_quantity', 18, 4)->default(0);
            $table->decimal('average_unit_cost', 18, 2)->default(0);
            $table->decimal('inventory_value', 18, 2)->default(0);
            $table->boolean('allow_negative_stock')->default(false);
            $table->timestamp('last_movement_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('stock_key')->index();
            $table->foreignId('inventory_stock_id')->nullable()->constrained('inventory_stocks')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $table->string('movement_type', 50);
            $table->string('direction', 10);
            $table->decimal('quantity', 18, 4);
            $table->decimal('before_quantity', 18, 4)->default(0);
            $table->decimal('after_quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('movement_value', 18, 2)->default(0);
            $table->decimal('before_value', 18, 2)->default(0);
            $table->decimal('after_value', 18, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason_code', 100)->nullable();
            $table->text('reason_text')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Company::query()->firstOrCreate(
            [
                'tenant_id' => 1,
                'slug' => 'default-company',
            ],
            [
                'id' => 1,
                'name' => 'Default Company',
                'code' => 'DEF',
                'is_active' => true,
            ]
        );

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId(1);
    }

    public function test_moving_average_actual_outcomes_match_opening_receipt_transfer_adjustment_and_return_flows(): void
    {
        $product = Product::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'name' => 'Produk Audit Average',
            'slug' => 'produk-audit-average',
            'sku' => 'AVG-001',
            'type' => 'physical',
            'track_stock' => true,
            'cost_price' => 12,
            'sell_price' => 20,
            'is_active' => true,
        ]);

        $warehouseA = InventoryLocation::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'code' => 'WH-A',
            'name' => 'Warehouse A',
            'type' => 'warehouse',
            'is_default' => true,
            'is_active' => true,
        ]);

        $warehouseB = InventoryLocation::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'code' => 'WH-B',
            'name' => 'Warehouse B',
            'type' => 'warehouse',
            'is_default' => false,
            'is_active' => true,
        ]);

        $mutation = app(StockMutationService::class);

        $opening = $mutation->record([
            'product_id' => $product->id,
            'inventory_location_id' => $warehouseA->id,
            'movement_type' => 'opening_stock',
            'direction' => 'in',
            'quantity' => 10,
            'unit_cost' => 10,
        ]);

        $receipt = $mutation->record([
            'product_id' => $product->id,
            'inventory_location_id' => $warehouseA->id,
            'movement_type' => 'purchase_receipt',
            'direction' => 'in',
            'quantity' => 5,
            'unit_cost' => 14,
        ]);

        $transferOut = $mutation->record([
            'product_id' => $product->id,
            'inventory_location_id' => $warehouseA->id,
            'movement_type' => 'transfer_out',
            'direction' => 'out',
            'quantity' => 3,
        ]);

        $transferIn = $mutation->record([
            'product_id' => $product->id,
            'inventory_location_id' => $warehouseB->id,
            'movement_type' => 'transfer_in',
            'direction' => 'in',
            'quantity' => 3,
            'unit_cost' => (float) $transferOut->unit_cost,
        ]);

        $adjustment = $mutation->record([
            'product_id' => $product->id,
            'inventory_location_id' => $warehouseA->id,
            'movement_type' => 'stock_adjustment',
            'direction' => 'out',
            'quantity' => 2,
        ]);

        $saleReturn = $mutation->record([
            'product_id' => $product->id,
            'inventory_location_id' => $warehouseA->id,
            'movement_type' => 'sale_return',
            'direction' => 'in',
            'quantity' => 1,
        ]);

        $stockA = StockBalance::query()->where('inventory_location_id', $warehouseA->id)->firstOrFail();
        $stockB = StockBalance::query()->where('inventory_location_id', $warehouseB->id)->firstOrFail();

        $this->assertSame(10.0, (float) $opening->unit_cost);
        $this->assertSame(14.0, (float) $receipt->unit_cost);
        $this->assertSame(11.33, (float) $transferOut->unit_cost);
        $this->assertSame(11.33, (float) $transferIn->unit_cost);
        $this->assertSame(11.33, (float) $adjustment->unit_cost);
        $this->assertSame(12.0, (float) $saleReturn->unit_cost);

        $this->assertSame(11.0, (float) $stockA->current_quantity);
        $this->assertSame(11.4, (float) $stockA->average_unit_cost);
        $this->assertSame(125.35, (float) $stockA->inventory_value);
        $this->assertSame(3.0, (float) $stockB->current_quantity);
        $this->assertSame(11.33, (float) $stockB->average_unit_cost);
        $this->assertSame(33.99, (float) $stockB->inventory_value);
    }
}
