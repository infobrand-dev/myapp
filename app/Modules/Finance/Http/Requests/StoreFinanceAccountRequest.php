<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\FinanceAccount;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinanceAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('finance.manage-categories') : false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', Rule::in(array_keys(FinanceAccount::typeOptions()))],
            'account_number' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'slug' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('finance_accounts', 'slug')->where(fn ($query) => $query
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'opening_balance' => $this->input('opening_balance') === '' ? null : $this->input('opening_balance'),
            'opening_balance_date' => $this->input('opening_balance_date') === '' ? null : $this->input('opening_balance_date'),
        ]);
    }
}
