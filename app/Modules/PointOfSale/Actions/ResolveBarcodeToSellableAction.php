<?php

namespace App\Modules\PointOfSale\Actions;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Support\BooleanQuery;
use Illuminate\Validation\ValidationException;

class ResolveBarcodeToSellableAction
{
    public function execute(string $barcode): array
    {
        $barcode = trim($barcode);

        if ($barcode === '') {
            throw ValidationException::withMessages([
                'barcode' => 'Barcode wajib diisi.',
            ]);
        }

        $variant = BooleanQuery::apply(
            ProductVariant::query()->with(['product.unit']),
            'is_active'
        )
            ->where('barcode', $barcode)
            ->first();

        if ($variant && $variant->product && $variant->product->is_active) {
            return [
                'product' => $variant->product,
                'variant' => $variant,
                'barcode' => $barcode,
            ];
        }

        $product = BooleanQuery::apply(
            Product::query()->with('unit'),
            'is_active'
        )
            ->where('barcode', $barcode)
            ->first();

        if ($product) {
            return [
                'product' => $product,
                'variant' => null,
                'barcode' => $barcode,
            ];
        }

        $variant = BooleanQuery::apply(
            ProductVariant::query()->with(['product.unit']),
            'is_active'
        )
            ->where('sku', $barcode)
            ->first();

        if ($variant && $variant->product && $variant->product->is_active) {
            return [
                'product' => $variant->product,
                'variant' => $variant,
                'barcode' => $barcode,
            ];
        }

        $product = BooleanQuery::apply(
            Product::query()->with('unit'),
            'is_active'
        )
            ->where('sku', $barcode)
            ->first();

        if ($product) {
            return [
                'product' => $product,
                'variant' => null,
                'barcode' => $barcode,
            ];
        }

        throw ValidationException::withMessages([
            'barcode' => 'Produk dengan barcode tersebut tidak ditemukan.',
        ]);
    }
}
