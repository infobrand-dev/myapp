<?php

namespace App\Modules\Inventory\Database\Seeders;

use App\Models\User;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\StockAdjustment;
use App\Modules\Inventory\Models\StockAdjustmentItem;
use App\Modules\Inventory\Models\StockBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockOpening;
use App\Modules\Inventory\Models\StockOpeningItem;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Inventory\Models\StockTransferItem;
use App\Modules\Products\Database\Seeders\ProductSampleSeeder;
use App\Modules\Products\Models\Product;
use Illuminate\Database\Seeder;

class InventorySampleSeeder extends Seeder
{
    public function run(): void
    {
        (new ProductSampleSeeder())->run();

        $user = User::query()->where('email', 'superadmin@myapp.test')->first() ?? User::query()->first();
        $product = Product::query()->where('sku', 'DEMO-COFFEE-250')->first();

        if (!$product) {
            return;
        }

        $main = InventoryLocation::query()->where('code', 'MAIN')->first();
        $store = InventoryLocation::query()->updateOrCreate(
            ['code' => 'STORE-01'],
            [
                'name' => 'Demo Store',
                'type' => 'store',
                'is_default' => false,
                'is_active' => true,
                'meta' => ['seeded' => true],
            ]
        );

        if (!$main) {
            $main = InventoryLocation::query()->updateOrCreate(
                ['code' => 'MAIN'],
                [
                    'name' => 'Main Warehouse',
                    'type' => 'warehouse',
                    'is_default' => true,
                    'is_active' => true,
                    'meta' => ['seeded' => true],
                ]
            );
        }

        $stock = StockBalance::query()->updateOrCreate(
            ['stock_key' => 'DEMO-COFFEE-250@MAIN'],
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'inventory_location_id' => $main->id,
                'current_quantity' => 24,
                'reserved_quantity' => 2,
                'minimum_quantity' => 5,
                'reorder_quantity' => 12,
                'allow_negative_stock' => false,
                'last_movement_at' => now()->subHour(),
                'meta' => ['seeded' => true],
            ]
        );

        $movement = StockMovement::query()->updateOrCreate(
            [
                'stock_key' => $stock->stock_key,
                'movement_type' => 'opening',
                'reference_type' => 'sample_data',
                'reference_id' => 1,
            ],
            [
                'inventory_stock_id' => $stock->id,
                'product_id' => $product->id,
                'product_variant_id' => null,
                'inventory_location_id' => $main->id,
                'direction' => 'in',
                'quantity' => 24,
                'before_quantity' => 0,
                'after_quantity' => 24,
                'reason_code' => 'initial_stock',
                'reason_text' => 'Opening stock sample.',
                'occurred_at' => now()->subHour(),
                'performed_by' => $user?->id,
                'approved_by' => $user?->id,
                'meta' => ['seeded' => true],
            ]
        );

        $opening = StockOpening::query()->updateOrCreate(
            ['code' => 'OPEN-DEMO-001'],
            [
                'inventory_location_id' => $main->id,
                'opening_date' => now()->subDays(7)->toDateString(),
                'status' => 'posted',
                'notes' => 'Opening stock sample.',
                'created_by' => $user?->id,
                'posted_by' => $user?->id,
                'posted_at' => now()->subDays(7),
                'meta' => ['seeded' => true],
            ]
        );

        StockOpeningItem::query()->updateOrCreate(
            ['opening_id' => $opening->id, 'product_id' => $product->id],
            [
                'product_variant_id' => null,
                'quantity' => 20,
                'minimum_quantity' => 5,
                'reorder_quantity' => 10,
                'movement_id' => $movement->id,
                'notes' => 'Opening line sample.',
            ]
        );

        $adjustment = StockAdjustment::query()->updateOrCreate(
            ['code' => 'ADJ-DEMO-001'],
            [
                'inventory_location_id' => $main->id,
                'adjustment_date' => now()->subDays(2)->toDateString(),
                'status' => 'posted',
                'reason_code' => 'stock_opname',
                'reason_text' => 'Adjustment sample setelah stock opname.',
                'created_by' => $user?->id,
                'approved_by' => $user?->id,
                'approved_at' => now()->subDays(2),
                'meta' => ['seeded' => true],
            ]
        );

        StockAdjustmentItem::query()->updateOrCreate(
            ['adjustment_id' => $adjustment->id, 'product_id' => $product->id],
            [
                'product_variant_id' => null,
                'direction' => 'in',
                'quantity' => 4,
                'notes' => 'Adjustment line sample.',
            ]
        );

        $transfer = StockTransfer::query()->updateOrCreate(
            ['code' => 'TRF-DEMO-001'],
            [
                'source_location_id' => $main->id,
                'destination_location_id' => $store->id,
                'transfer_date' => now()->subDay()->toDateString(),
                'status' => 'sent',
                'notes' => 'Transfer sample ke toko.',
                'created_by' => $user?->id,
                'approved_by' => $user?->id,
                'sent_by' => $user?->id,
                'approved_at' => now()->subDay(),
                'sent_at' => now()->subDay(),
                'meta' => ['seeded' => true],
            ]
        );

        StockTransferItem::query()->updateOrCreate(
            ['transfer_id' => $transfer->id, 'product_id' => $product->id],
            [
                'product_variant_id' => null,
                'requested_quantity' => 6,
                'sent_quantity' => 6,
                'received_quantity' => 0,
                'notes' => 'Transfer line sample.',
            ]
        );
    }
}
