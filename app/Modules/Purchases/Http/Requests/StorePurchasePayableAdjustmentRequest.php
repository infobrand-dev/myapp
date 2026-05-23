<?php

namespace App\Modules\Purchases\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchasePayableAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('purchases.manage_payable_adjustments') : false;
    }

    public function rules(): array
    {
        return [
            'adjustment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
