<?php

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReceivableDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales.manage_receivable_adjustments') : false;
    }

    public function rules(): array
    {
        return [
            'dispute_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:160'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
