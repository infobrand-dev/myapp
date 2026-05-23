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
            'notes' => ['nullable', 'string'],
            'closure_reason' => ['nullable', 'string', 'max:255'],
            'force_complete' => ['nullable', 'boolean'],
        ];
    }
}
