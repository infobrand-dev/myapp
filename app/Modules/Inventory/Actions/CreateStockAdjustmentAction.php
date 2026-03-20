<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Inventory\Models\StockAdjustment;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateStockAdjustmentAction
{
    public function execute(array $data, ?User $actor = null): StockAdjustment
    {
        return DB::transaction(function () use ($data, $actor) {
            $adjustment = StockAdjustment::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'code' => 'ADJ-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'inventory_location_id' => $data['inventory_location_id'],
                'adjustment_date' => $data['adjustment_date'],
                'status' => StockAdjustment::STATUS_DRAFT,
                'reason_code' => $data['reason_code'],
                'reason_text' => $data['reason_text'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor ? $actor->id : null,
                'meta' => [
                    'created_via' => 'inventory_stock_adjustment',
                ],
            ]);

            foreach ($data['items'] as $item) {
                $adjustment->items()->create([
                    'tenant_id' => TenantContext::currentId(),
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'direction' => $item['direction'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $adjustment->load(['location', 'items.product', 'items.variant']);
        });
    }
}
