<?php

namespace App\Modules\Products\Repositories;

use App\Modules\Products\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository
{
    public function paginateForIndex(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()
            ->select('products.*')
            ->with(['category', 'brand', 'unit'])
            ->withCount([
                'variants as variant_count' => fn (Builder $query) => $query->whereNull('deleted_at'),
            ])
            ->withSum('stocks as total_stock', 'quantity')
            ->withSum('stocks as reserved_stock', 'reserved_quantity');

        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('products.created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForDetail(Product $product): Product
    {
        return $product->load([
            'category.parent',
            'brand',
            'unit',
            'optionGroups.values',
            'prices.priceLevel',
            'stocks.location',
            'media',
            'variants' => fn ($query) => $query
                ->whereNull('deleted_at')
                ->with(['optionValues.group', 'prices.priceLevel', 'stocks.location', 'media'])
                ->withSum('stocks as total_stock', 'quantity')
                ->orderBy('position'),
        ])->loadSum('stocks as total_stock', 'quantity');
    }

    public function findForEdit(Product $product): Product
    {
        return $product->load([
            'prices.priceLevel',
            'gallery',
            'variants' => fn ($query) => $query
                ->whereNull('deleted_at')
                ->with(['optionValues.group', 'stocks.location'])
                ->orderBy('position'),
        ]);
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
                        $variantQuery->whereNull('deleted_at')
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
            $query->where('products.is_active', true);
        }

        if (($filters['status'] ?? null) === 'inactive') {
            $query->where('products.is_active', false);
        }

        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('products.brand_id', $filters['brand_id']);
        }

        $stockStatus = $filters['stock_status'] ?? null;
        if ($stockStatus === 'non_stock') {
            $query->where('products.track_stock', false);
        }

        if ($stockStatus === 'out_of_stock') {
            $query->where('products.track_stock', true)
                ->havingRaw('COALESCE(total_stock, 0) <= 0');
        }

        if ($stockStatus === 'low_stock') {
            $query->where('products.track_stock', true)
                ->havingRaw('COALESCE(total_stock, 0) > 0')
                ->havingRaw('COALESCE(total_stock, 0) <= products.min_stock');
        }

        if ($stockStatus === 'in_stock') {
            $query->where('products.track_stock', true)
                ->havingRaw('COALESCE(total_stock, 0) > products.min_stock');
        }

        return $query;
    }
}
