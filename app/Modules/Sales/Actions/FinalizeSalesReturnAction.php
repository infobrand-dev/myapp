<?php

namespace App\Modules\Sales\Actions;

use App\Models\User;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeSalesReturnAction
{
    private const TENANT_ID = 1;

    private $validateReturnableItems;
    private $integrateReturnToInventory;
    private $syncRefundSummary;

    public function __construct(
        ValidateReturnableItemsAction $validateReturnableItems,
        IntegrateReturnToInventoryAction $integrateReturnToInventory,
        SyncSaleReturnRefundSummaryAction $syncRefundSummary
    ) {
        $this->validateReturnableItems = $validateReturnableItems;
        $this->integrateReturnToInventory = $integrateReturnToInventory;
        $this->syncRefundSummary = $syncRefundSummary;
    }

    public function execute(SaleReturn $saleReturn, ?User $actor = null): SaleReturn
    {
        $saleReturn = DB::transaction(function () use ($saleReturn, $actor) {
            $saleReturn = SaleReturn::query()
                ->where('tenant_id', self::TENANT_ID)
                ->with(['items', 'sale.items'])
                ->lockForUpdate()
                ->findOrFail($saleReturn->id);

            if (!$saleReturn->isDraft()) {
                throw ValidationException::withMessages([
                    'sale_return' => 'Hanya draft sales return yang dapat di-finalize.',
                ]);
            }

            /** @var Sale $sale */
            $sale = $saleReturn->sale;
            if (!$sale || !$sale->isFinalized()) {
                throw ValidationException::withMessages([
                    'sale_return' => 'Sale asal harus tetap finalized untuk memproses return.',
                ]);
            }

            $requestedItems = $saleReturn->items->map(fn ($item) => [
                'sale_item_id' => $item->sale_item_id,
                'qty_returned' => $item->qty_returned,
            ])->all();

            $this->validateReturnableItems->execute($sale, $requestedItems, $saleReturn->id);

            $fromStatus = $saleReturn->status;
            $saleReturn->update([
                'status' => SaleReturn::STATUS_FINALIZED,
                'finalized_at' => now(),
                'updated_by' => $actor ? $actor->id : null,
                'finalized_by' => $actor ? $actor->id : null,
            ]);

            $saleReturn->statusLogs()->create([
                'tenant_id' => self::TENANT_ID,
                'from_status' => $fromStatus,
                'to_status' => SaleReturn::STATUS_FINALIZED,
                'event' => 'finalized',
                'reason' => $saleReturn->reason,
                'meta' => [
                    'refund_required' => $saleReturn->refund_required,
                    'inventory_restock_required' => $saleReturn->inventory_restock_required,
                ],
                'actor_id' => $actor ? $actor->id : null,
            ]);

            return $saleReturn->refresh()->load(['items', 'sale']);
        });

        $saleReturn = $this->integrateReturnToInventory->execute($saleReturn->loadMissing('items'), $actor);

        return $this->syncRefundSummary->execute($saleReturn);
    }
}
