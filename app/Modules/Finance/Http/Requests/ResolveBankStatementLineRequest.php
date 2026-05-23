<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\BankStatementLine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveBankStatementLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('finance.manage-reconciliation') : false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:exception,ignored,unmatched'],
            'resolution_reason' => [
                Rule::requiredIf(in_array((string) $this->input('status'), ['exception', 'ignored'], true)),
                'nullable',
                'string',
                'max:120',
                Rule::in(array_keys(BankStatementLine::resolutionReasonOptions())),
            ],
            'resolution_note' => ['nullable', 'string'],
        ];
    }
}
