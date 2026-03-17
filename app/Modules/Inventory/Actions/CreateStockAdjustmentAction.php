<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Inventory\Models\StockAdjustment;
use App\Modules\Inventory\Services\StockMutationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateStockAdjustmentAction
{
    public function __construct(private readonly StockMutationService $mutationService)
    {
    }

    public function execute(array $data, ?User $actor = null): StockAdjustment
    {
        return DB::transaction(function () use ($data, $actor) {
            $adjustment = StockAdjustment::query()->create([
                'code' => 'ADJ-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'inventory_location_id' => $data['inventory_location_id'],
                'adjustment_date' => $data['adjustment_date'],
                'status' => 'posted',
                'reason_code' => $data['reason_code'],
                'reason_text' => $data['reason_text'],
                'created_by' => $actor?->id,
                'approved_by' => $actor?->id,
                'approved_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $movement = $this->mutationService->record([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'inventory_location_id' => $data['inventory_location_id'],
                    'movement_type' => $item['movement_type'] ?? 'stock_adjustment',
                    'direction' => $item['direction'],
                    'quantity' => $item['quantity'],
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'reason_code' => $data['reason_code'],
                    'reason_text' => $item['notes'] ?? $data['reason_text'],
                    'occurred_at' => $data['adjustment_date'] . ' 00:00:00',
                    'performed_by' => $actor,
                    'approved_by' => $actor,
                ]);

                $adjustment->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'direction' => $item['direction'],
                    'quantity' => $item['quantity'],
                    'movement_id' => $movement->id,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $adjustment->load(['location', 'items.product', 'items.variant']);
        });
    }
}
