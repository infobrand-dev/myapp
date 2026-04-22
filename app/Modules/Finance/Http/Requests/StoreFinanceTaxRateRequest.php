<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\FinanceTaxRate;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinanceTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('finance.manage-tax') : false;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('finance_tax_rates', 'code')->where(fn ($query) => $query
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())),
            ],
            'name' => ['required', 'string', 'max:150'],
            'tax_type' => ['required', Rule::in(array_keys(FinanceTaxRate::taxTypeOptions()))],
            'tax_scope' => ['nullable', Rule::in(array_keys(FinanceTaxRate::taxScopeOptions()))],
            'jurisdiction_code' => ['nullable', 'string', 'max:10'],
            'legal_basis' => ['nullable', 'string', 'max:150'],
            'document_label' => ['nullable', 'string', 'max:100'],
            'requires_tax_number' => ['nullable', 'boolean'],
            'requires_counterparty_tax_id' => ['nullable', 'boolean'],
            'rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_inclusive' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sales_account_code' => ['nullable', 'string', 'max:100'],
            'purchase_account_code' => ['nullable', 'string', 'max:100'],
            'withholding_account_code' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ];
    }
}
