<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\StockBalance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockRepository
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = StockBalance::query()
            ->where('tenant_id', $this->tenantId())
            ->with(['product', 'variant', 'location'])
            ->when(!empty($filters['location_id']), fn ($builder) => $builder->where('inventory_location_id', $filters['location_id']))
            ->when(!empty($filters['product_id']), fn ($builder) => $builder->where('product_id', $filters['product_id']))
            ->when(!empty($filters['status']), function ($builder) use ($filters) {
                if ($filters['status'] === 'out_of_stock') {
                    $builder->where('current_quantity', '<=', 0);
                }

                if ($filters['status'] === 'low_stock') {
                    $builder->where('current_quantity', '>', 0)->whereColumn('current_quantity', '<=', 'minimum_quantity');
                }

                if ($filters['status'] === 'in_stock') {
                    $builder->where(function ($query) {
                        $query->where(function ($nested) {
                            $nested->where('minimum_quantity', '<=', 0)
                                ->where('current_quantity', '>', 0);
                        })->orWhere(function ($nested) {
                            $nested->where('minimum_quantity', '>', 0)
                                ->whereColumn('current_quantity', '>', 'minimum_quantity');
                        });
                    });
                }
            })
            ->when(!empty($filters['search']), function ($builder) use ($filters) {
                $search = trim((string) $filters['search']);
                $builder->where(function ($query) use ($search) {
                    $query->whereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                        ->orWhereHas('variant', fn ($variant) => $variant->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                        ->orWhereHas('location', fn ($location) => $location->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
                });
            });

        return $query->orderByDesc('updated_at')->paginate($perPage)->withQueryString();
    }

    public function findOrFail(int $id): StockBalance
    {
        return StockBalance::query()
            ->where('tenant_id', $this->tenantId())
            ->with(['product', 'variant', 'location', 'movements.performer'])
            ->findOrFail($id);
    }

    public function locations(): Collection
    {
        return InventoryLocation::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function snapshotByLocation(int $locationId): Collection
    {
        return StockBalance::query()
            ->where('tenant_id', $this->tenantId())
            ->with(['product', 'variant', 'location'])
            ->where('inventory_location_id', $locationId)
            ->orderBy('product_id')
            ->orderBy('product_variant_id')
            ->get();
    }

    private function tenantId(): int
    {
        return 1;
    }
}
