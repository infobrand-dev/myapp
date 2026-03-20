<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('inventory.manage-stock-adjustment') : false;
    }

    public function rules(): array
    {
        return [
            'inventory_location_id' => ['required', 'integer', Rule::exists('inventory_locations', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'adjustment_date' => ['required', 'date'],
            'reason_code' => ['required', 'string', 'max:100'],
            'reason_text' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'items.*.product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'items.*.direction' => ['required', Rule::in(['in', 'out'])],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('items', []) as $index => $item) {
                $productId = isset($item['product_id']) ? (int) $item['product_id'] : null;
                $variantId = isset($item['product_variant_id']) && $item['product_variant_id'] !== ''
                    ? (int) $item['product_variant_id']
                    : null;

                if (!$productId) {
                    continue;
                }

                $product = Product::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->find($productId);

                if (!$product || !$product->track_stock) {
                    $validator->errors()->add("items.$index.product_id", 'Produk non-stockable tidak bisa di-adjust.');
                    continue;
                }

                if (!$variantId) {
                    continue;
                }

                $variant = ProductVariant::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->find($variantId);

                if (!$variant || (int) $variant->product_id !== $productId) {
                    $validator->errors()->add("items.$index.product_variant_id", 'Variant tidak cocok dengan produk yang dipilih.');
                    continue;
                }

                if (!$variant->track_stock) {
                    $validator->errors()->add("items.$index.product_variant_id", 'Variant non-stockable tidak bisa di-adjust.');
                }
            }

            $locationId = $this->input('inventory_location_id');
            if (!$locationId) {
                return;
            }

            $location = \App\Modules\Inventory\Models\InventoryLocation::query()
                ->where('tenant_id', TenantContext::currentId())
                ->find($locationId);

            if (!$location) {
                $validator->errors()->add('inventory_location_id', 'Lokasi inventory tidak tersedia untuk tenant aktif.');
            }
        });
    }
}
