<?php

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Models\SaleReceivableDispute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveSaleReceivableDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales.manage_receivable_adjustments') : false;
    }

    public function rules(): array
    {
        return [
            'outcome_type' => ['required', Rule::in(array_keys(SaleReceivableDispute::outcomeOptions()))],
            'resolution_note' => ['nullable', 'string'],
        ];
    }
}
