<?php

namespace App\Modules\Sales\Http\Requests;

class StoreSaleQuotationRequest extends StoreDraftSaleRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales_quotation.create') : false;
    }

    public function rules(): array
    {
        $rules = parent::rules();

        unset(
            $rules['external_reference'],
            $rules['inventory_location_id'],
            $rules['payment_status'],
            $rules['source'],
            $rules['due_date'],
            $rules['attachment']
        );

        $rules['quotation_date'] = ['required', 'date'];
        $rules['valid_until_date'] = ['nullable', 'date'];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeSalePayload();

        $this->merge([
            'quotation_date' => $this->filled('quotation_date') ? $this->input('quotation_date') : now()->format('Y-m-d\TH:i'),
            'valid_until_date' => $this->filled('valid_until_date') ? $this->input('valid_until_date') : null,
        ]);
    }

    protected function validateBusinessRules(\Illuminate\Validation\Validator $validator): void
    {
        foreach ($this->input('items', []) as $index => $item) {
            if (empty($item['product_id'])) {
                $validator->errors()->add("items.{$index}.sellable_key", 'Produk wajib valid.');
            }
        }

        if ($this->filled('valid_until_date') && $this->filled('quotation_date')) {
            $validUntil = \Carbon\Carbon::parse((string) $this->input('valid_until_date'))->startOfDay();
            $quotationDate = \Carbon\Carbon::parse((string) $this->input('quotation_date'))->startOfDay();

            if ($validUntil->lt($quotationDate)) {
                $validator->errors()->add('valid_until_date', 'Tanggal berlaku quotation tidak boleh lebih awal dari tanggal quotation.');
            }
        }
    }
}
