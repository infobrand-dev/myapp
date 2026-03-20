<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\Models\StockOpname;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateStockOpnameAction
{
    private const TENANT_ID = 1;

    public function execute(StockOpname $opname, array $data): StockOpname
    {
        return DB::transaction(function () use ($opname, $data) {
            $opname = StockOpname::query()
                ->where('tenant_id', self::TENANT_ID)
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($opname->id);

            if (!$opname->isDraft()) {
                throw new DomainException('Stock opname yang sudah finalized tidak dapat diubah.');
            }

            $opname->forceFill([
                'opname_date' => $data['opname_date'],
                'notes' => isset($data['notes']) ? $data['notes'] : null,
            ])->save();

            $itemsById = $opname->items->keyBy('id');

            foreach ($data['items'] as $itemData) {
                $item = $itemsById->get((int) $itemData['id']);

                if (!$item) {
                    continue;
                }

                $physicalQuantity = array_key_exists('physical_quantity', $itemData) && $itemData['physical_quantity'] !== null && $itemData['physical_quantity'] !== ''
                    ? round((float) $itemData['physical_quantity'], 4)
                    : null;

                $item->forceFill([
                    'physical_quantity' => $physicalQuantity,
                    'difference_quantity' => $physicalQuantity === null ? null : round($physicalQuantity - (float) $item->system_quantity, 4),
                    'notes' => isset($itemData['notes']) ? $itemData['notes'] : null,
                ])->save();
            }

            return $opname->load(['location', 'creator', 'items.product', 'items.variant']);
        });
    }
}
