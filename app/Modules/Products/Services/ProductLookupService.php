<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Models\ProductBrand;
use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Models\ProductPriceLevel;
use App\Modules\Products\Models\ProductUnit;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductLookupService
{
    public function categories(): Collection
    {
        return ProductCategory::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function brands(): Collection
    {
        return ProductBrand::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function units(): Collection
    {
        return ProductUnit::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function priceLevels(): Collection
    {
        return ProductPriceLevel::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
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
                'notes' => 'Lookup category, brand, unit, dan price level disediakan internal agar module bisa berdiri sendiri.',
            ],
            [
                'module' => 'inventory',
                'type' => 'optional',
                'status' => 'recommended',
                'notes' => 'Inventory menjadi sumber kebenaran stok, mutasi, lokasi, stock card, dan low stock monitoring.',
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
            if (!ProductCategory::query()->where('tenant_id', TenantContext::currentId())->find($categoryId)) {
                throw new DomainException('Kategori produk tidak tersedia untuk tenant aktif.');
            }

            return (int) $categoryId;
        }

        $name = trim((string) $newCategoryName);
        if ($name === '') {
            return null;
        }

        $category = ProductCategory::query()->firstOrCreate(
            ['tenant_id' => TenantContext::currentId(), 'slug' => Str::slug($name)],
            ['tenant_id' => TenantContext::currentId(), 'name' => $name, 'is_active' => true]
        );

        return (int) $category->id;
    }

    private function resolveBrandId($brandId, $newBrandName): ?int
    {
        if ($brandId) {
            if (!ProductBrand::query()->where('tenant_id', TenantContext::currentId())->find($brandId)) {
                throw new DomainException('Brand produk tidak tersedia untuk tenant aktif.');
            }

            return (int) $brandId;
        }

        $name = trim((string) $newBrandName);
        if ($name === '') {
            return null;
        }

        $brand = ProductBrand::query()->firstOrCreate(
            ['tenant_id' => TenantContext::currentId(), 'slug' => Str::slug($name)],
            ['tenant_id' => TenantContext::currentId(), 'name' => $name, 'is_active' => true]
        );

        return (int) $brand->id;
    }

    private function resolveUnitId($unitId, $newUnitName, $newUnitCode): ?int
    {
        if ($unitId) {
            if (!ProductUnit::query()->where('tenant_id', TenantContext::currentId())->find($unitId)) {
                throw new DomainException('Unit produk tidak tersedia untuk tenant aktif.');
            }

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
            ['tenant_id' => TenantContext::currentId(), 'code' => Str::upper($code)],
            ['tenant_id' => TenantContext::currentId(), 'name' => $name, 'is_active' => true]
        );

        return (int) $unit->id;
    }
}
