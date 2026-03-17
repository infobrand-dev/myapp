<?php

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Http\Requests\Concerns\NormalizesSalePayload;
use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDraftSaleRequest extends FormRequest
{
    use NormalizesSalePayload;

    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales.create') : false;
    }

    public function rules(): array
    {
        return [
            'external_reference' => ['nullable', 'string', 'max:100'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
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
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
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
            fn (Validator $validator) => $this->validateBusinessRules($validator),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeSalePayload();
    }

    protected function validateBusinessRules(Validator $validator): void
    {
        foreach ($this->input('items', []) as $index => $item) {
            if (empty($item['product_id'])) {
                $validator->errors()->add("items.{$index}.sellable_key", 'Produk wajib valid.');
            }
        }
    }
}
