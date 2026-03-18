<?php

namespace App\Modules\Purchases\Http\Requests;

use App\Modules\Purchases\Http\Requests\Concerns\NormalizesPurchasePayload;
use Illuminate\Foundation\Http\FormRequest;

class StoreDraftPurchaseRequest extends FormRequest
{
    use NormalizesPurchasePayload;

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
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'purchase_date' => ['required', 'date'],
            'supplier_reference' => ['nullable', 'string', 'max:100'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'supplier_notes' => ['nullable', 'string'],
            'currency_code' => ['required', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
