<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteBankReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('finance.manage-reconciliation') : false;
    }

    public function rules(): array
    {
        return [
            'payment_ids' => ['nullable', 'array'],
            'payment_ids.*' => ['integer'],
            'statement_line_ids' => ['nullable', 'array'],
            'statement_line_ids.*' => ['integer'],
            'statement_matches' => ['nullable', 'array'],
            'statement_matches.*.target_type' => ['nullable', 'in:payment,finance_transaction'],
            'statement_matches.*.target_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
