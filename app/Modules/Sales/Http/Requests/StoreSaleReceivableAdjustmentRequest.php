<?php

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReceivableAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales.manage_receivable_adjustments') : false;
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
