<?php

namespace App\Modules\Inventory\Actions;

use App\Models\User;
use App\Modules\Inventory\Models\StockAdjustment;
use App\Modules\Inventory\Services\StockMutationService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Support\Facades\DB;

class FinalizeStockAdjustmentAction
{
    private $mutationService;

    public function __construct(StockMutationService $mutationService)
    {
        $this->mutationService = $mutationService;
    }

    public function execute(StockAdjustment $adjustment, ?User $actor = null): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor) {
            $adjustment = StockAdjustment::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with(['items.product', 'items.variant'])
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->lockForUpdate()
                ->findOrFail($adjustment->id);

            if (!$adjustment->isDraft()) {
                throw new DomainException('Stock adjustment yang sudah finalized tidak dapat diposting ulang.');
            }

            if ($adjustment->items->isEmpty()) {
                throw new DomainException('Stock adjustment tidak memiliki item untuk diposting.');
            }

            foreach ($adjustment->items as $item) {
                $movement = $this->mutationService->record([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'inventory_location_id' => $adjustment->inventory_location_id,
                    'movement_type' => 'stock_adjustment',
                    'direction' => $item->direction,
                    'quantity' => $item->quantity,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'reason_code' => $adjustment->reason_code,
                    'reason_text' => $item->notes ?: $adjustment->reason_text,
                    'occurred_at' => $adjustment->adjustment_date->toDateString() . ' 00:00:00',
                    'performed_by' => $adjustment->created_by,
                    'approved_by' => $actor,
                    'meta' => [
                        'adjustment_item_id' => $item->id,
                        'adjustment_notes' => $adjustment->notes,
                    ],
                ]);

                $item->forceFill([
                    'movement_id' => $movement->id,
                ])->save();
            }

            $adjustment->forceFill([
                'status' => StockAdjustment::STATUS_FINALIZED,
                'finalized_by' => $actor ? $actor->id : null,
                'finalized_at' => now(),
            ])->save();

            return $adjustment->load([
                'location',
                'creator',
                'finalizer',
                'items.product',
                'items.variant',
                'items.movement',
            ]);
        });
    }
}
