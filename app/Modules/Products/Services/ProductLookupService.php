<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Models\ProductBrand;
use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Models\ProductPriceLevel;
use App\Modules\Products\Models\ProductUnit;
use App\Modules\Products\Models\StockLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductLookupService
{
    public function categories(): Collection
    {
        return ProductCategory::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function brands(): Collection
    {
        return ProductBrand::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function units(): Collection
    {
        return ProductUnit::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function priceLevels(): Collection
    {
        return ProductPriceLevel::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    public function stockLocations(): Collection
    {
        return StockLocation::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
    }

    public function defaultStockLocation(): ?StockLocation
    {
        return StockLocation::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('id')->first();
    }

    public function resolveLookupIds(array $data): array
    {
        $data['category_id'] = $this->resolveCategoryId($data['category_id'] ?? null, $data['new_category_name'] ?? null);
        $data['brand_id'] = $this->resolveBrandId($data['brand_id'] ?? null, $data['new_brand_name'] ?? null);
        $data['unit_id'] = $this->resolveUnitId($data['unit_id'] ?? null, $data['new_unit_name'] ?? null, $data['new_unit_code'] ?? null);

        return $data;
    }

    public function dependencyMap(): array
    {
        return [
            [
                'module' => 'products',
                'type' => 'required',
                'status' => 'internal',
                'notes' => 'Lookup category, brand, unit, price level, dan stock location disediakan internal agar module bisa berdiri sendiri.',
            ],
            [
                'module' => 'inventory',
                'type' => 'optional',
                'status' => 'future',
                'notes' => 'Dapat mengambil alih mutasi stok dan stock valuation tanpa mengubah kontrak data Products.',
            ],
            [
                'module' => 'outlets',
                'type' => 'optional',
                'status' => 'future',
                'notes' => 'Table stock location sudah siap dipetakan ke outlet atau warehouse terpisah.',
            ],
            [
                'module' => 'sales',
                'type' => 'optional',
                'status' => 'future',
                'notes' => 'Sales akan mengonsumsi harga efektif, status aktif, dan lock penghapusan produk yang sudah dipakai transaksi.',
            ],
        ];
    }

    private function resolveCategoryId($categoryId, $newCategoryName): ?int
    {
        if ($categoryId) {
            return (int) $categoryId;
        }

        $name = trim((string) $newCategoryName);
        if ($name === '') {
            return null;
        }

        $category = ProductCategory::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'is_active' => true]
        );

        return (int) $category->id;
    }

    private function resolveBrandId($brandId, $newBrandName): ?int
    {
        if ($brandId) {
            return (int) $brandId;
        }

        $name = trim((string) $newBrandName);
        if ($name === '') {
            return null;
        }

        $brand = ProductBrand::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'is_active' => true]
        );

        return (int) $brand->id;
    }

    private function resolveUnitId($unitId, $newUnitName, $newUnitCode): ?int
    {
        if ($unitId) {
            return (int) $unitId;
        }

        $name = trim((string) $newUnitName);
        if ($name === '') {
            return null;
        }

        $code = trim((string) $newUnitCode);
        if ($code === '') {
            $code = Str::upper(Str::slug($name, '_'));
        }

        $unit = ProductUnit::query()->firstOrCreate(
            ['code' => Str::upper($code)],
            ['name' => $name, 'is_active' => true]
        );

        return (int) $unit->id;
    }
}
