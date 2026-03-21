<?php

namespace App\Modules\Reports\Services;

use Illuminate\Support\Facades\DB;

class InventoryReportService extends BaseReportService
{
    public function filters(array $filters): array
    {
        return array_merge($this->baseFilters($filters), [
            'location_id' => $this->normalizeInt($filters['location_id'] ?? null),
            'product' => $this->normalizeString($filters['product'] ?? null),
            'movement_type' => $this->normalizeString($filters['movement_type'] ?? null),
        ]);
    }

    public function data(array $filters): array
    {
        return [
            'summary' => $this->summary($filters),
            'stockList' => $this->stockList($filters),
            'lowStock' => $this->lowStock($filters),
            'stockMovement' => $this->stockMovement($filters),
            'adjustments' => $this->adjustments($filters),
            'opnames' => $this->opnames($filters),
        ];
    }

    public function summary(array $filters): array
    {
        $stockQuery = $this->stockBaseQuery($filters);
        $movementQuery = $this->movementBaseQuery($filters);

        return [
            'stock_rows' => (clone $stockQuery)->count('inventory_stocks.id'),
            'total_quantity' => round((float) (clone $stockQuery)->sum('inventory_stocks.current_quantity'), 2),
            'low_stock_count' => (clone $stockQuery)
                ->whereColumn('inventory_stocks.current_quantity', '<=', 'inventory_stocks.minimum_quantity')
                ->where('inventory_stocks.minimum_quantity', '>', 0)
                ->count('inventory_stocks.id'),
            'movement_count' => (clone $movementQuery)->count('inventory_stock_movements.id'),
        ];
    }

    public function stockList(array $filters)
    {
        return $this->stockBaseQuery($filters)
            ->select('inventory_stocks.*', 'products.name as product_name', 'product_variants.name as variant_name', 'inventory_locations.name as location_name')
            ->selectRaw('(inventory_stocks.current_quantity - inventory_stocks.reserved_quantity) as available_quantity')
            ->orderBy('products.name')
            ->limit(20)
            ->get();
    }

    public function lowStock(array $filters)
    {
        return $this->stockBaseQuery($filters)
            ->select('inventory_stocks.*', 'products.name as product_name', 'product_variants.name as variant_name', 'inventory_locations.name as location_name')
            ->whereColumn('inventory_stocks.current_quantity', '<=', 'inventory_stocks.minimum_quantity')
            ->where('inventory_stocks.minimum_quantity', '>', 0)
            ->orderByRaw('(inventory_stocks.minimum_quantity - inventory_stocks.current_quantity) DESC')
            ->limit(15)
            ->get();
    }

    public function stockMovement(array $filters)
    {
        return $this->movementBaseQuery($filters)
            ->selectRaw('inventory_stock_movements.movement_type')
            ->selectRaw('inventory_stock_movements.direction')
            ->selectRaw('COUNT(inventory_stock_movements.id) as movement_count')
            ->selectRaw('SUM(inventory_stock_movements.quantity) as total_quantity')
            ->groupBy('inventory_stock_movements.movement_type', 'inventory_stock_movements.direction')
            ->orderByDesc('movement_count')
            ->get();
    }

    public function adjustments(array $filters)
    {
        $query = DB::table('inventory_stock_adjustments')
            ->leftJoin('inventory_locations', 'inventory_locations.id', '=', 'inventory_stock_adjustments.inventory_location_id')
            ->leftJoin('inventory_stock_adjustment_items', 'inventory_stock_adjustment_items.adjustment_id', '=', 'inventory_stock_adjustments.id');

        $this->applyTenantCompanyBranchScope($query, 'inventory_stock_adjustments');
        $this->applyDateRange($query, 'inventory_stock_adjustments.adjustment_date', $filters);

        return $query
            ->when(!empty($filters['location_id']), fn ($builder) => $builder->where('inventory_stock_adjustments.inventory_location_id', $filters['location_id']))
            ->selectRaw('inventory_stock_adjustments.code')
            ->selectRaw('inventory_stock_adjustments.adjustment_date')
            ->selectRaw('inventory_stock_adjustments.status')
            ->selectRaw('inventory_locations.name as location_name')
            ->selectRaw('COUNT(inventory_stock_adjustment_items.id) as line_count')
            ->groupBy('inventory_stock_adjustments.id', 'inventory_stock_adjustments.code', 'inventory_stock_adjustments.adjustment_date', 'inventory_stock_adjustments.status', 'inventory_locations.name')
            ->orderByDesc('inventory_stock_adjustments.adjustment_date')
            ->limit(15)
            ->get();
    }

    public function opnames(array $filters)
    {
        $query = DB::table('inventory_stock_opnames')
            ->leftJoin('inventory_locations', 'inventory_locations.id', '=', 'inventory_stock_opnames.inventory_location_id')
            ->leftJoin('inventory_stock_opname_items', 'inventory_stock_opname_items.opname_id', '=', 'inventory_stock_opnames.id');

        $this->applyTenantCompanyBranchScope($query, 'inventory_stock_opnames');
        $this->applyDateRange($query, 'inventory_stock_opnames.opname_date', $filters);

        return $query
            ->when(!empty($filters['location_id']), fn ($builder) => $builder->where('inventory_stock_opnames.inventory_location_id', $filters['location_id']))
            ->selectRaw('inventory_stock_opnames.code')
            ->selectRaw('inventory_stock_opnames.opname_date')
            ->selectRaw('inventory_stock_opnames.status')
            ->selectRaw('inventory_locations.name as location_name')
            ->selectRaw('COUNT(inventory_stock_opname_items.id) as line_count')
            ->selectRaw('SUM(ABS(inventory_stock_opname_items.difference_quantity)) as absolute_difference_qty')
            ->groupBy('inventory_stock_opnames.id', 'inventory_stock_opnames.code', 'inventory_stock_opnames.opname_date', 'inventory_stock_opnames.status', 'inventory_locations.name')
            ->orderByDesc('inventory_stock_opnames.opname_date')
            ->limit(15)
            ->get();
    }

    public function summaryOnly(array $filters): array
    {
        return $this->summary($filters);
    }

    private function stockBaseQuery(array $filters)
    {
        $query = DB::table('inventory_stocks')
            ->leftJoin('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_stocks.product_variant_id')
            ->leftJoin('inventory_locations', 'inventory_locations.id', '=', 'inventory_stocks.inventory_location_id');

        $this->applyTenantCompanyBranchScope($query, 'inventory_stocks');

        return $query
            ->when(!empty($filters['location_id']), fn ($builder) => $builder->where('inventory_stocks.inventory_location_id', $filters['location_id']))
            ->when(!empty($filters['product']), function ($builder) use ($filters) {
                $builder->where(function ($nested) use ($filters) {
                    $nested
                        ->where('products.name', 'like', '%' . $filters['product'] . '%')
                        ->orWhere('product_variants.name', 'like', '%' . $filters['product'] . '%');
                });
            });
    }

    private function movementBaseQuery(array $filters)
    {
        $query = DB::table('inventory_stock_movements')
            ->leftJoin('products', 'products.id', '=', 'inventory_stock_movements.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_stock_movements.product_variant_id');

        $this->applyTenantCompanyBranchScope($query, 'inventory_stock_movements');
        $this->applyDateRange($query, 'inventory_stock_movements.occurred_at', $filters);

        return $query
            ->when(!empty($filters['location_id']), fn ($builder) => $builder->where('inventory_stock_movements.inventory_location_id', $filters['location_id']))
            ->when(!empty($filters['movement_type']), fn ($builder) => $builder->where('inventory_stock_movements.movement_type', $filters['movement_type']))
            ->when(!empty($filters['product']), function ($builder) use ($filters) {
                $builder->where(function ($nested) use ($filters) {
                    $nested
                        ->where('products.name', 'like', '%' . $filters['product'] . '%')
                        ->orWhere('product_variants.name', 'like', '%' . $filters['product'] . '%');
                });
            });
    }
}
