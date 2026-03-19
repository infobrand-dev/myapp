<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Inventory\Models\StockBalance;
use App\Modules\Inventory\Models\StockOpname;
use App\Modules\Inventory\Services\StockMutationService;
use DomainException;
use Illuminate\Support\Facades\DB;

class FinalizeStockOpnameAction
{
    private $createAdjustment;

    private $finalizeAdjustment;

    private $mutationService;

    public function __construct(
        CreateStockAdjustmentAction $createAdjustment,
        FinalizeStockAdjustmentAction $finalizeAdjustment,
        StockMutationService $mutationService
    ) {
        $this->createAdjustment = $createAdjustment;
        $this->finalizeAdjustment = $finalizeAdjustment;
        $this->mutationService = $mutationService;
    }

    public function execute(StockOpname $opname, ?User $actor = null): StockOpname
    {
        return DB::transaction(function () use ($opname, $actor) {
            $opname = StockOpname::query()
                ->with(['items.product', 'items.variant'])
                ->lockForUpdate()
                ->findOrFail($opname->id);

            if (!$opname->isDraft()) {
                throw new DomainException('Stock opname yang sudah finalized tidak dapat diposting ulang.');
            }

            if ($opname->items->isEmpty()) {
                throw new DomainException('Stock opname tidak memiliki item.');
            }

            $adjustmentItems = [];

            foreach ($opname->items as $item) {
                if ($item->physical_quantity === null) {
                    throw new DomainException('Semua item stock opname harus memiliki stok fisik sebelum finalize.');
                }

                $stockKey = $this->mutationService->stockKey(
                    (int) $item->product_id,
                    $item->product_variant_id ? (int) $item->product_variant_id : null,
                    (int) $opname->inventory_location_id
                );

                $stock = StockBalance::query()
                    ->where('stock_key', $stockKey)
                    ->lockForUpdate()
                    ->first();

                $currentSystemQuantity = $stock ? round((float) $stock->current_quantity, 4) : 0.0;
                $physicalQuantity = round((float) $item->physical_quantity, 4);
                $adjustmentQuantity = round($physicalQuantity - $currentSystemQuantity, 4);

                $item->forceFill([
                    'final_system_quantity' => $currentSystemQuantity,
                    'adjustment_quantity' => $adjustmentQuantity,
                    'difference_quantity' => round($physicalQuantity - (float) $item->system_quantity, 4),
                ])->save();

                if ($adjustmentQuantity === 0.0) {
                    continue;
                }

                $adjustmentItems[] = [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'direction' => $adjustmentQuantity > 0 ? 'in' : 'out',
                    'quantity' => abs($adjustmentQuantity),
                    'notes' => 'Stock opname ' . $opname->code . ' | selisih snapshot ' . number_format((float) $item->difference_quantity, 4, '.', ''),
                ];
            }

            $adjustment = null;

            if (!empty($adjustmentItems)) {
                $adjustment = $this->createAdjustment->execute([
                    'inventory_location_id' => $opname->inventory_location_id,
                    'adjustment_date' => $opname->opname_date->toDateString(),
                    'reason_code' => 'stock_opname',
                    'reason_text' => 'Adjustment hasil stock opname ' . $opname->code,
                    'notes' => $opname->notes,
                    'items' => $adjustmentItems,
                ], $actor);

                $adjustment = $this->finalizeAdjustment->execute($adjustment, $actor);
            }

            $opname->forceFill([
                'status' => StockOpname::STATUS_FINALIZED,
                'finalized_by' => $actor ? $actor->id : null,
                'finalized_at' => now(),
                'adjustment_id' => $adjustment ? $adjustment->id : null,
            ])->save();

            return $opname->load([
                'location',
                'creator',
                'finalizer',
                'adjustment',
                'items.product',
                'items.variant',
            ]);
        });
    }
}
