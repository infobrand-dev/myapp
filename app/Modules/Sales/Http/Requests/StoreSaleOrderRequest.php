<?php

namespace App\Modules\Sales\Http\Requests;

use Carbon\Carbon;
use Illuminate\Validation\Validator;

class StoreSaleOrderRequest extends StoreDraftSaleRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales_order.create') : false;
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

        $rules['order_date'] = ['required', 'date'];
        $rules['expected_delivery_date'] = ['nullable', 'date'];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeSalePayload();

        $this->merge([
            'order_date' => $this->filled('order_date') ? $this->input('order_date') : now()->format('Y-m-d\TH:i'),
            'expected_delivery_date' => $this->filled('expected_delivery_date') ? $this->input('expected_delivery_date') : null,
        ]);
    }

    protected function validateBusinessRules(Validator $validator): void
    {
        foreach ($this->input('items', []) as $index => $item) {
            if (empty($item['product_id'])) {
                $validator->errors()->add("items.{$index}.sellable_key", 'Produk wajib valid.');
            }
        }

        if ($this->filled('expected_delivery_date') && $this->filled('order_date')) {
            $deliveryDate = Carbon::parse((string) $this->input('expected_delivery_date'))->startOfDay();
            $orderDate = Carbon::parse((string) $this->input('order_date'))->startOfDay();

            if ($deliveryDate->lt($orderDate)) {
                $validator->errors()->add('expected_delivery_date', 'Tanggal delivery tidak boleh lebih awal dari tanggal order.');
            }
        }
    }
}
