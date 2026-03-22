<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\StockBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryDashboardRepository
{
    public function summary(?int $locationId = null): array
    {
        $stocks = StockBalance::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->when($locationId, fn ($query) => $query->where('inventory_location_id', $locationId));
        $movements = StockMovement::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->when($locationId, fn ($query) => $query->where('inventory_location_id', $locationId));
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return [
            'stock_items' => (clone $stocks)->count(),
            'total_quantity' => (float) (clone $stocks)->sum('current_quantity'),
            'low_stock_count' => (clone $stocks)
                ->where('current_quantity', '>', 0)
                ->whereColumn('current_quantity', '<=', 'minimum_quantity')
                ->count(),
            'out_of_stock_count' => (clone $stocks)->where('current_quantity', '<=', 0)->count(),
            'movement_today_count' => (clone $movements)->whereBetween('occurred_at', [$todayStart, $todayEnd])->count(),
        ];
    }

    public function movementBreakdown(?int $locationId = null, int $limit = 6): Collection
    {
        return StockMovement::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->when($locationId, fn ($query) => $query->where('inventory_location_id', $locationId))
            ->select([
                'movement_type',
                'direction',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(quantity) as total_quantity'),
            ])
            ->groupBy('movement_type', 'direction')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function criticalStocks(?int $locationId = null, int $limit = 8): Collection
    {
        return StockBalance::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->with(['product', 'variant', 'location'])
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->when($locationId, fn ($query) => $query->where('inventory_location_id', $locationId))
            ->where(function ($query) {
                $query->where('current_quantity', '<=', 0)
                    ->orWhere(function ($nested) {
                        $nested->where('minimum_quantity', '>', 0)
                            ->whereColumn('current_quantity', '<=', 'minimum_quantity');
                    });
            })
            ->orderByRaw('CASE WHEN current_quantity <= 0 THEN 0 ELSE 1 END')
            ->orderByRaw('(current_quantity - minimum_quantity) asc')
            ->orderBy('current_quantity')
            ->limit($limit)
            ->get();
    }
}
