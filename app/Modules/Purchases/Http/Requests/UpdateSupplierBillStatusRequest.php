<?php

namespace App\Modules\Purchases\Http\Requests;

use App\Modules\Purchases\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierBillStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('purchases.manage_supplier_bill') : false;
    }

    public function rules(): array
    {
        $status = (string) $this->route('status');

        return [
            'supplier_bill_received_at' => ['nullable', 'date'],
            'supplier_invoice_number' => [
                Rule::requiredIf($status === Purchase::BILL_VERIFIED),
                'nullable',
                'string',
                'max:100',
            ],
            'supplier_reference' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string'],
        ];
    }
}
