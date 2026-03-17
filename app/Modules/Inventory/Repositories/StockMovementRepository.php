<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockMovementRepository
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return StockMovement::query()
            ->with(['product', 'variant', 'location', 'performer'])
            ->when(!empty($filters['location_id']), fn ($query) => $query->where('inventory_location_id', $filters['location_id']))
            ->when(!empty($filters['movement_type']), fn ($query) => $query->where('movement_type', $filters['movement_type']))
            ->when(!empty($filters['product_id']), fn ($query) => $query->where('product_id', $filters['product_id']))
            ->when(!empty($filters['date_from']), fn ($query) => $query->whereDate('occurred_at', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($query) => $query->whereDate('occurred_at', '<=', $filters['date_to']))
            ->when(!empty($filters['reference_type']), fn ($query) => $query->where('reference_type', $filters['reference_type']))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
