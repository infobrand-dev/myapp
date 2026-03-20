<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStockTransferRequest extends FormRequest
{
    private const TENANT_ID = 1;

    public function authorize(): bool
    {
        return $this->user()?->can('inventory.manage-stock-transfer') ?? false;
    }

    public function rules(): array
    {
        return [
            'source_location_id' => ['required', 'integer', Rule::exists('inventory_locations', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'destination_location_id' => ['required', 'integer', 'different:source_location_id', Rule::exists('inventory_locations', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'transfer_date' => ['required', 'date'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'items.*.product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'items.*.requested_quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $sourceLocationId = $this->input('source_location_id');
                if ($sourceLocationId && !InventoryLocation::query()->where('tenant_id', self::TENANT_ID)->find($sourceLocationId)) {
                    $validator->errors()->add('source_location_id', 'Lokasi asal tidak valid untuk tenant aktif.');
                }

                $destinationLocationId = $this->input('destination_location_id');
                if ($destinationLocationId && !InventoryLocation::query()->where('tenant_id', self::TENANT_ID)->find($destinationLocationId)) {
                    $validator->errors()->add('destination_location_id', 'Lokasi tujuan tidak valid untuk tenant aktif.');
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
