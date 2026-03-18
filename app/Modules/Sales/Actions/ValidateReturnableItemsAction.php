<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Validation\ValidationException;

class ValidateReturnableItemsAction
{
    public function execute(Sale $sale, array $requestedItems, ?int $ignoreSaleReturnId = null): array
    {
        if (!$sale->isFinalized()) {
            throw ValidationException::withMessages([
                'sale_id' => 'Hanya sale finalized yang dapat diretur.',
            ]);
        }

        $finalizedReturns = SaleReturn::query()
            ->with('items')
            ->where('sale_id', $sale->id)
            ->where('status', SaleReturn::STATUS_FINALIZED)
            ->when($ignoreSaleReturnId, fn ($query) => $query->whereKeyNot($ignoreSaleReturnId))
            ->get();

        $returnedQtyMap = [];
        foreach ($finalizedReturns as $saleReturn) {
            foreach ($saleReturn->items as $item) {
                $returnedQtyMap[$item->sale_item_id] = round(($returnedQtyMap[$item->sale_item_id] ?? 0) + (float) $item->qty_returned, 4);
            }
        }

        $returnableMap = [];
        foreach ($sale->items as $saleItem) {
            $alreadyReturned = round((float) ($returnedQtyMap[$saleItem->id] ?? 0), 4);
            $saleQty = round((float) $saleItem->qty, 4);
            $remaining = max(0, round($saleQty - $alreadyReturned, 4));

            $returnableMap[$saleItem->id] = [
                'sale_qty' => $saleQty,
                'returned_qty' => $alreadyReturned,
                'remaining_qty' => $remaining,
            ];
        }

        foreach ($requestedItems as $index => $row) {
            $saleItemId = (int) ($row['sale_item_id'] ?? 0);
            $qty = round((float) ($row['qty_returned'] ?? 0), 4);

            if ($saleItemId <= 0 || $qty <= 0) {
                continue;
            }

            if (!isset($returnableMap[$saleItemId])) {
                throw ValidationException::withMessages([
                    "items.{$index}.sale_item_id" => 'Item sale asal tidak valid.',
                ]);
            }

            if ($qty > (float) $returnableMap[$saleItemId]['remaining_qty']) {
                throw ValidationException::withMessages([
                    "items.{$index}.qty_returned" => 'Qty return melebihi qty tersisa yang belum pernah diretur.',
                ]);
            }
        }

        return $returnableMap;
    }
}
