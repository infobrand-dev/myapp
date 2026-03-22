<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\StockMovement;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockMovementRepository
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return StockMovement::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->with(['product', 'variant', 'location', 'performer'])
            ->when(!empty($filters['location_id']), fn ($query) => $query->where('inventory_location_id', $filters['location_id']))
            ->when(!empty($filters['movement_type']), fn ($query) => $query->where('movement_type', $filters['movement_type']))
            ->when(!empty($filters['product_id']), fn ($query) => $query->where('product_id', $filters['product_id']))
            ->when(!empty($filters['date_from']), fn ($query) => $query->where('occurred_at', '>=', $filters['date_from'] . ' 00:00:00'))
            ->when(!empty($filters['date_to']), fn ($query) => $query->where('occurred_at', '<=', $filters['date_to'] . ' 23:59:59'))
            ->when(!empty($filters['reference_type']), fn ($query) => $query->where('reference_type', $filters['reference_type']))
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
