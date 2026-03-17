<?php

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordSalePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('payments.create') : false;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in([
                Sale::PAYMENT_METHOD_CASH,
                Sale::PAYMENT_METHOD_BANK_TRANSFER,
                Sale::PAYMENT_METHOD_CARD,
                Sale::PAYMENT_METHOD_EWALLET,
                Sale::PAYMENT_METHOD_QRIS,
                Sale::PAYMENT_METHOD_OTHER,
            ])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'payment_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency_code' => $this->filled('currency_code') ? strtoupper((string) $this->input('currency_code')) : 'IDR',
        ]);
    }
}
