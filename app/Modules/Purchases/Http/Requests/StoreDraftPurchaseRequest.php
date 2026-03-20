<?php

namespace App\Modules\Purchases\Http\Requests;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Modules\Purchases\Http\Requests\Concerns\NormalizesPurchasePayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDraftPurchaseRequest extends FormRequest
{
    use NormalizesPurchasePayload;

    private const TENANT_ID = 1;

    protected function prepareForValidation(): void
    {
        $this->normalizePurchasePayload();
    }

    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('purchases.create') : false;
    }

    public function rules(): array
    {
        return [
            'contact_id' => ['required', 'integer', Rule::exists('contacts', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'purchase_date' => ['required', 'date'],
            'supplier_reference' => ['nullable', 'string', 'max:100'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'supplier_notes' => ['nullable', 'string'],
            'currency_code' => ['required', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'items.*.product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
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
        $contactId = $this->input('contact_id');
        if ($contactId && !Contact::query()->where('tenant_id', self::TENANT_ID)->find($contactId)) {
            $validator->errors()->add('contact_id', 'Supplier tidak tersedia untuk tenant aktif.');
        }

        foreach ((array) $this->input('items', []) as $index => $item) {
            $productId = $item['product_id'] ?? null;
            if ($productId && !Product::query()->where('tenant_id', self::TENANT_ID)->find($productId)) {
                $validator->errors()->add("items.$index.product_id", 'Produk tidak tersedia untuk tenant aktif.');
            }

            $variantId = $item['product_variant_id'] ?? null;
            if ($variantId && !ProductVariant::query()->where('tenant_id', self::TENANT_ID)->find($variantId)) {
                $validator->errors()->add("items.$index.product_variant_id", 'Varian produk tidak tersedia untuk tenant aktif.');
            }
        }
    }
}
