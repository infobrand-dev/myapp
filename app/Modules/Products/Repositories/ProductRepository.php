<?php

namespace App\Modules\Products\Repositories;

use App\Modules\Products\Models\Product;
use App\Support\BooleanQuery;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository
{
    public function paginateForIndex(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()
            ->where('products.tenant_id', $this->tenantId())
            ->select('products.*')
            ->with(['category', 'brand', 'unit'])
            ->with('defaultSupplier')
            ->withCount([
                'variants as variant_count' => fn (Builder $query) => $query
                    ->where('tenant_id', $this->tenantId())
                    ->whereNull('deleted_at'),
            ]);

        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('products.created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForDetail(Product $product): Product
    {
        return Product::query()
            ->where('tenant_id', $this->tenantId())
            ->with([
            'category.parent',
            'brand',
            'unit',
            'defaultSupplier',
            'optionGroups.values',
            'prices.priceLevel',
            'media',
            'variants' => fn ($query) => $query
                ->where('tenant_id', $this->tenantId())
                ->whereNull('deleted_at')
                ->with(['optionValues.group', 'prices.priceLevel', 'media'])
                ->orderBy('position'),
            ])
            ->findOrFail($product->id);
    }

    public function findForEdit(Product $product): Product
    {
        return Product::query()
            ->where('tenant_id', $this->tenantId())
            ->with([
            'prices.priceLevel',
            'gallery',
            'defaultSupplier',
            'variants' => fn ($query) => $query
                ->where('tenant_id', $this->tenantId())
                ->whereNull('deleted_at')
                ->with(['optionValues.group'])
                ->orderBy('position'),
            ])
            ->findOrFail($product->id);
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%")
                    ->orWhereHas('variants', function (Builder $variantQuery) use ($search) {
                        $variantQuery->where('tenant_id', $this->tenantId())
                            ->whereNull('deleted_at')
                            ->where(function (Builder $nested) use ($search) {
                                $nested->where('name', 'like', "%{$search}%")
                                    ->orWhere('sku', 'like', "%{$search}%")
                                    ->orWhere('barcode', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if (!empty($filters['type'])) {
            $query->where('products.type', $filters['type']);
        }

        if (($filters['status'] ?? null) === 'active') {
            BooleanQuery::apply($query, 'products.is_active');
        }

        if (($filters['status'] ?? null) === 'inactive') {
            BooleanQuery::apply($query, 'products.is_active', false);
        }

        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('products.brand_id', $filters['brand_id']);
        }

        return $query;
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }
}
