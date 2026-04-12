<?php

namespace App\Modules\Products\Services;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactScope;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductBrand;
use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Models\ProductPriceLevel;
use App\Modules\Products\Models\ProductUnit;
use App\Modules\Products\Models\ProductVariant;
use App\Support\BooleanQuery;
use App\Support\CurrencySettingsResolver;
use App\Support\MoneyFormatter;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductLookupService
{
    private MoneyFormatter $money;

    private CurrencySettingsResolver $currencies;

    public function __construct(
        MoneyFormatter $money,
        CurrencySettingsResolver $currencies
    ) {
        $this->money = $money;
        $this->currencies = $currencies;
    }

    /**
     * Returns a flat list of products + variants suitable for autocomplete widgets.
     * Each item contains both sell_price and cost_price so consumers can pick the
     * field they need without re-querying.
     *
     * Shape: { key, label, description, sell_price, cost_price }
     */
    public function forAutocomplete(): Collection
    {
        $defaultCurrency = $this->currencies->defaultCurrency();

        $products = BooleanQuery::apply(
            Product::query()->with([
                'unit',
                'variants' => fn ($query) => BooleanQuery::apply(
                    $query->whereNull('deleted_at')->orderBy('position'),
                    'is_active'
                ),
            ]),
            'is_active'
        )
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return $products->flatMap(function (Product $product) use ($defaultCurrency) {
            $currency = $product->currency_code ?: $defaultCurrency;

            $rows = collect([[
                'key'         => 'product:' . $product->id,
                'product_id'  => $product->id,
                'variant_id'  => null,
                'label'       => $product->name,
                'description' => implode(' | ', array_filter([
                    $product->sku ? 'SKU: ' . $product->sku : null,
                    optional($product->unit)->name ? 'Unit: ' . optional($product->unit)->name : null,
                    'Sell: ' . $this->money->format((float) $product->sell_price, $currency),
                    'Cost: ' . $this->money->format((float) $product->cost_price, $currency),
                ])),
                'sell_price'  => (float) $product->sell_price,
                'cost_price'  => (float) $product->cost_price,
            ]]);

            $variants = $product->variants->map(function (ProductVariant $variant) use ($product, $defaultCurrency) {
                $currency = $variant->currency_code ?: $product->currency_code ?: $defaultCurrency;

                return [
                    'key'         => 'variant:' . $variant->id,
                    'product_id'  => $product->id,
                    'variant_id'  => $variant->id,
                    'label'       => $product->name . ' — ' . $variant->name,
                    'description' => implode(' | ', array_filter([
                        $variant->sku ? 'SKU: ' . $variant->sku : null,
                        $variant->attribute_summary ?: null,
                        'Sell: ' . $this->money->format((float) $variant->sell_price, $currency),
                        'Cost: ' . $this->money->format((float) $variant->cost_price, $currency),
                    ])),
                    'sell_price'  => (float) $variant->sell_price,
                    'cost_price'  => (float) $variant->cost_price,
                ];
            });

            return $rows->concat($variants);
        })->values();
    }

    public function categories(): Collection
    {
        return BooleanQuery::apply(
            ProductCategory::query()
                ->where('tenant_id', TenantContext::currentId()),
            'is_active'
        )
            ->orderBy('name')
            ->get();
    }

    public function brands(): Collection
    {
        return BooleanQuery::apply(
            ProductBrand::query()
                ->where('tenant_id', TenantContext::currentId()),
            'is_active'
        )
            ->orderBy('name')
            ->get();
    }

    public function units(): Collection
    {
        return BooleanQuery::apply(
            ProductUnit::query()
                ->where('tenant_id', TenantContext::currentId()),
            'is_active'
        )
            ->orderBy('name')
            ->get();
    }

    public function priceLevels(): Collection
    {
        return BooleanQuery::apply(
            ProductPriceLevel::query()
                ->where('tenant_id', TenantContext::currentId()),
            'is_active'
        )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function suppliers(): Collection
    {
        return BooleanQuery::apply(
            Contact::query()->tap(fn ($query) => ContactScope::applyVisibilityScope($query)),
            'is_active'
        )
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
            ['tenant_id' => TenantContext::currentId(), 'name' => $name, 'is_active' => $this->databaseBoolean(true)]
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
            ['tenant_id' => TenantContext::currentId(), 'name' => $name, 'is_active' => $this->databaseBoolean(true)]
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
            ['tenant_id' => TenantContext::currentId(), 'name' => $name, 'is_active' => $this->databaseBoolean(true)]
        );

        return (int) $unit->id;
    }

    private function databaseBoolean(bool $value): bool|string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? ($value ? 'true' : 'false')
            : $value;
    }
}
