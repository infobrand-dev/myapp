<?php

namespace App\Modules\PointOfSale\Http\Requests;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Support\BooleanQuery;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePosCartItemRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user() ? $this->user()->can('pos.use') : false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'qty' => ['nullable', 'numeric', 'min:0.0001'],
            'barcode_scanned' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateTenantRelations($validator),
        ];
    }

    private function validateTenantRelations(Validator $validator): void
    {
        $productId = $this->input('product_id');
        if ($productId && !BooleanQuery::apply(
            Product::query()->where('tenant_id', TenantContext::currentId()),
            'is_active'
        )->find($productId)) {
            $validator->errors()->add('product_id', 'Produk tidak tersedia untuk tenant aktif.');
        }

        $variantId = $this->input('product_variant_id');
        if ($variantId && !ProductVariant::query()->where('tenant_id', TenantContext::currentId())->find($variantId)) {
            $validator->errors()->add('product_variant_id', 'Varian produk tidak tersedia untuk tenant aktif.');
        }
    }
}
