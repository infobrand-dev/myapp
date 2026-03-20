<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOpeningStockRequest extends FormRequest
{
    private const TENANT_ID = 1;

    public function authorize(): bool
    {
        return $this->user()?->can('inventory.manage-opening-stock') ?? false;
    }

    public function rules(): array
    {
        return [
            'inventory_location_id' => ['required', 'integer', Rule::exists('inventory_locations', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'opening_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'items.*.product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.minimum_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $locationId = $this->input('inventory_location_id');
                if ($locationId && !InventoryLocation::query()->where('tenant_id', self::TENANT_ID)->find($locationId)) {
                    $validator->errors()->add('inventory_location_id', 'Lokasi inventory tidak valid untuk tenant aktif.');
                }

                foreach ((array) $this->input('items', []) as $index => $item) {
                    $productId = $item['product_id'] ?? null;
                    if ($productId && !Product::query()->where('tenant_id', self::TENANT_ID)->find($productId)) {
                        $validator->errors()->add("items.$index.product_id", 'Produk tidak valid untuk tenant aktif.');
                    }

                    $variantId = $item['product_variant_id'] ?? null;
                    if ($variantId && !ProductVariant::query()->where('tenant_id', self::TENANT_ID)->find($variantId)) {
                        $validator->errors()->add("items.$index.product_variant_id", 'Varian produk tidak valid untuk tenant aktif.');
                    }
                }
            },
        ];
    }
}
