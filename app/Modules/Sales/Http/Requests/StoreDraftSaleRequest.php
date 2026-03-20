<?php

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Modules\Sales\Http\Requests\Concerns\NormalizesSalePayload;
use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDraftSaleRequest extends FormRequest
{
    use NormalizesSalePayload;

    private const TENANT_ID = 1;

    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales.create') : false;
    }

    public function rules(): array
    {
        return [
            'external_reference' => ['nullable', 'string', 'max:100'],
            'contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'payment_status' => ['required', Rule::in([
                Sale::PAYMENT_UNPAID,
                Sale::PAYMENT_PARTIAL,
                Sale::PAYMENT_PAID,
                Sale::PAYMENT_REFUNDED,
            ])],
            'source' => ['required', Rule::in([
                Sale::SOURCE_MANUAL,
                Sale::SOURCE_POS,
                Sale::SOURCE_ONLINE,
                Sale::SOURCE_API,
            ])],
            'transaction_date' => ['required', 'date'],
            'currency_code' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sellable_key' => ['required', 'string', 'max:50'],
            'items.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'items.*.product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateTenantRelations($validator),
            fn (Validator $validator) => $this->validateBusinessRules($validator),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeSalePayload();
    }

    protected function validateBusinessRules(Validator $validator): void
    {
        $source = $this->input('source');
        $externalReference = trim((string) $this->input('external_reference', ''));

        if (in_array($source, [Sale::SOURCE_POS, Sale::SOURCE_ONLINE, Sale::SOURCE_API], true) && $externalReference === '') {
            $validator->errors()->add('external_reference', 'External reference wajib diisi untuk source POS, online, atau API.');
        }

        foreach ($this->input('items', []) as $index => $item) {
            if (empty($item['product_id'])) {
                $validator->errors()->add("items.{$index}.sellable_key", 'Produk wajib valid.');
            }
        }
    }

    protected function validateTenantRelations(Validator $validator): void
    {
        $contactId = $this->input('contact_id');
        if ($contactId && !Contact::query()->where('tenant_id', self::TENANT_ID)->find($contactId)) {
            $validator->errors()->add('contact_id', 'Contact tidak tersedia untuk tenant aktif.');
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
