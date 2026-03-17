<?php

namespace App\Modules\Discounts\Services;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductBrand;
use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Models\ProductVariant;

class DiscountReferenceService
{
    public function formOptions(): array
    {
        return [
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'category_id', 'brand_id']),
            'variants' => ProductVariant::query()->with('product:id,name')->orderBy('name')->get(['id', 'product_id', 'name', 'sku']),
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
            'brands' => ProductBrand::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function hydrateCartItems(array $items): array
    {
        $productIds = collect($items)->pluck('product_id')->filter()->unique()->values();
        $variantIds = collect($items)->pluck('variant_id')->filter()->unique()->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get(['id', 'name', 'category_id', 'brand_id'])
            ->keyBy('id');

        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->get(['id', 'product_id', 'name'])
            ->keyBy('id');

        return collect($items)->values()->map(function (array $item, int $index) use ($products, $variants) {
            $product = $products->get($item['product_id'] ?? null);
            $variant = $variants->get($item['variant_id'] ?? null);

            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $subtotal = (float) ($item['subtotal'] ?? ($quantity * $unitPrice));

            return [
                'line_key' => (string) ($item['line_key'] ?? ('line-' . $index)),
                'product_id' => $item['product_id'] ?? $variant?->product_id ?? null,
                'variant_id' => $item['variant_id'] ?? null,
                'product_name' => $item['product_name'] ?? $product?->name,
                'variant_name' => $item['variant_name'] ?? $variant?->name,
                'category_id' => $item['category_id'] ?? $product?->category_id,
                'brand_id' => $item['brand_id'] ?? $product?->brand_id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'meta' => $item['meta'] ?? [],
            ];
        })->all();
    }
}
