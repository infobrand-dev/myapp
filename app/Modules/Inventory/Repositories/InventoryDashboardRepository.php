<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\StockBalance;
use App\Modules\Inventory\Models\StockMovement;

class InventoryDashboardRepository
{
    public function summary(?int $locationId = null): array
    {
        $stocks = StockBalance::query()->when($locationId, fn ($query) => $query->where('inventory_location_id', $locationId));
        $movements = StockMovement::query()->when($locationId, fn ($query) => $query->where('inventory_location_id', $locationId));

        return [
            'stock_items' => (clone $stocks)->count(),
            'total_quantity' => (float) (clone $stocks)->sum('current_quantity'),
            'low_stock_count' => (clone $stocks)
                ->where('current_quantity', '>', 0)
                ->whereColumn('current_quantity', '<=', 'minimum_quantity')
                ->count(),
            'out_of_stock_count' => (clone $stocks)->where('current_quantity', '<=', 0)->count(),
            'movement_today_count' => (clone $movements)->whereDate('occurred_at', now()->toDateString())->count(),
        ];
    }
}
