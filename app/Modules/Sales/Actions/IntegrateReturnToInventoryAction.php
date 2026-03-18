<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Inventory\Services\StockMutationService;
use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Support\Facades\DB;
use Throwable;

class IntegrateReturnToInventoryAction
{
    public function execute(SaleReturn $saleReturn, ?User $actor = null): SaleReturn
    {
        if (!$saleReturn->inventory_restock_required) {
            $saleReturn->update([
                'inventory_status' => SaleReturn::INVENTORY_SKIPPED,
            ]);

            return $saleReturn->refresh();
        }

        if (!$saleReturn->inventory_location_id || !class_exists(StockMutationService::class)) {
            $saleReturn->update([
                'inventory_status' => SaleReturn::INVENTORY_FAILED,
                'integration_snapshot' => array_merge($saleReturn->integration_snapshot ?? [], [
                    'inventory' => [
                        'status' => 'failed',
                        'message' => 'Inventory location atau service inventory belum tersedia.',
                    ],
                ]),
            ]);

            return $saleReturn->refresh();
        }

        /** @var StockMutationService $stockMutation */
        $stockMutation = app(StockMutationService::class);

        try {
            DB::transaction(function () use ($saleReturn, $stockMutation, $actor) {
                foreach ($saleReturn->items as $item) {
                    if (!$item->product_id) {
                        continue;
                    }

                    $stockMutation->record([
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'inventory_location_id' => $saleReturn->inventory_location_id,
                        'movement_type' => 'sale_return',
                        'direction' => 'in',
                        'quantity' => $item->qty_returned,
                        'reference_type' => $saleReturn->getMorphClass(),
                        'reference_id' => $saleReturn->getKey(),
                        'reason_code' => 'sale_return',
                        'reason_text' => $saleReturn->reason,
                        'occurred_at' => $saleReturn->finalized_at ?: now(),
                        'performed_by' => $actor ? $actor->id : null,
                        'approved_by' => $actor ? $actor->id : null,
                        'meta' => [
                            'return_number' => $saleReturn->return_number,
                            'sale_number' => $saleReturn->sale_number_snapshot,
                            'sale_item_id' => $item->sale_item_id,
                        ],
                    ]);
                }
            });
        } catch (Throwable $exception) {
            $saleReturn->update([
                'inventory_status' => SaleReturn::INVENTORY_FAILED,
                'integration_snapshot' => array_merge($saleReturn->integration_snapshot ?? [], [
                    'inventory' => [
                        'status' => 'failed',
                        'message' => $exception->getMessage(),
                    ],
                ]),
            ]);

            throw $exception;
        }

        $saleReturn->update([
            'inventory_status' => SaleReturn::INVENTORY_COMPLETED,
            'integration_snapshot' => array_merge($saleReturn->integration_snapshot ?? [], [
                'inventory' => [
                    'status' => 'completed',
                    'location_id' => $saleReturn->inventory_location_id,
                    'processed_at' => now()->toDateTimeString(),
                ],
            ]),
        ]);

        return $saleReturn->refresh();
    }
}
