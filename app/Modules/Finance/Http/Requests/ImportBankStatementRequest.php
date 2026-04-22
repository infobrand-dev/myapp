<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportBankStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('finance.manage-reconciliation') : false;
    }

    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ];
    }
}
