<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Repositories\InventoryDashboardRepository;
use App\Modules\Inventory\Repositories\StockRepository;

class InventoryDashboardService
{
    public function __construct(
        private readonly InventoryDashboardRepository $dashboardRepository,
        private readonly StockRepository $stockRepository
    ) {
    }

    public function data(?int $locationId = null): array
    {
        return [
            'summary' => $this->dashboardRepository->summary($locationId),
            'recentMovements' => StockMovement::query()
                ->with(['product', 'variant', 'location', 'performer'])
                ->when($locationId, fn ($query) => $query->where('inventory_location_id', $locationId))
                ->orderByDesc('occurred_at')
                ->limit(10)
                ->get(),
            'lowStocks' => $this->stockRepository->paginate([
                'location_id' => $locationId,
                'status' => 'low_stock',
            ], 10),
        ];
    }
}
