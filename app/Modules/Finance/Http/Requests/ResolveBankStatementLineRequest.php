<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'resolution_reason' => ['nullable', 'string', 'max:120'],
            'resolution_note' => ['nullable', 'string'],
        ];
    }
}
