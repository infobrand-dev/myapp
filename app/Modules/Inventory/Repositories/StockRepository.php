<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\StockBalance;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockRepository
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildStockQuery($filters);

        return $query->orderByDesc('updated_at')->paginate($perPage)->withQueryString();
    }

    public function summary(array $filters = []): array
    {
        $query = $this->buildStockQuery($filters);

        return [
            'total_items' => (clone $query)->count(),
            'out_of_stock' => (clone $query)->where('current_quantity', '<=', 0)->count(),
            'low_stock' => (clone $query)
                ->where('current_quantity', '>', 0)
                ->whereColumn('current_quantity', '<=', 'minimum_quantity')
                ->count(),
            'reserved_risk' => (clone $query)
                ->where('current_quantity', '>', 0)
                ->whereRaw('(current_quantity - reserved_quantity) <= 0')
                ->count(),
            'reorder_candidates' => (clone $query)
                ->where('reorder_quantity', '>', 0)
                ->whereColumn('current_quantity', '<=', 'reorder_quantity')
                ->count(),
        ];
    }

    public function findOrFail(int $id): StockBalance
    {
        return StockBalance::query()
            ->where('tenant_id', $this->tenantId())
            ->where('company_id', $this->companyId())
            ->with(['product', 'variant', 'location', 'movements.performer'])
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->findOrFail($id);
    }

    public function locations(): Collection
    {
        return InventoryLocation::query()
            ->where('tenant_id', $this->tenantId())
            ->where('company_id', $this->companyId())
            ->where('is_active', true)
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function snapshotByLocation(int $locationId): Collection
    {
        return StockBalance::query()
            ->where('tenant_id', $this->tenantId())
            ->where('company_id', $this->companyId())
            ->with(['product', 'variant', 'location'])
            ->where('inventory_location_id', $locationId)
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->orderBy('product_id')
            ->orderBy('product_variant_id')
            ->get();
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }

    private function companyId(): int
    {
        return (int) CompanyContext::currentId();
    }

    private function buildStockQuery(array $filters)
    {
        $query = StockBalance::query()
            ->where('tenant_id', $this->tenantId())
            ->where('company_id', $this->companyId())
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

                if ($filters['status'] === 'reserved_risk') {
                    $builder->where('current_quantity', '>', 0)
                        ->whereRaw('(current_quantity - reserved_quantity) <= 0');
                }

                if ($filters['status'] === 'reorder') {
                    $builder->where('reorder_quantity', '>', 0)
                        ->whereColumn('current_quantity', '<=', 'reorder_quantity');
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

        BranchContext::applyScope($query);

        return $query;
    }
}
