<?php

namespace App\Modules\Purchases\Http\Requests;

class StorePurchaseOrderRequest extends StoreDraftPurchaseRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('purchase_order.create') : false;
    }

    public function rules(): array
    {
        $rules = parent::rules();

        unset(
            $rules['due_date'],
            $rules['supplier_reference'],
            $rules['supplier_invoice_number'],
            $rules['supplier_bill_status'],
            $rules['supplier_bill_received_at'],
            $rules['supplier_notes']
        );

        $rules['order_date'] = ['required', 'date'];
        $rules['expected_receive_date'] = ['nullable', 'date'];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePurchasePayload();

        $this->merge([
            'order_date' => $this->filled('order_date') ? $this->input('order_date') : now()->format('Y-m-d\TH:i'),
            'expected_receive_date' => $this->filled('expected_receive_date') ? $this->input('expected_receive_date') : null,
        ]);
    }
}
