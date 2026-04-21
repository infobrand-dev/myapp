<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\ChartOfAccount;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('finance.manage-coa') : false;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('chart_of_accounts', 'code')->where(fn ($query) => $query
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())),
            ],
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
            'account_type' => ['required', Rule::in(array_keys(ChartOfAccount::typeOptions()))],
            'normal_balance' => ['required', Rule::in(array_keys(ChartOfAccount::normalBalanceOptions()))],
            'report_section' => ['required', Rule::in(array_keys(ChartOfAccount::reportSectionOptions()))],
            'is_postable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code', ''))),
            'sort_order' => $this->input('sort_order') === '' ? 0 : $this->input('sort_order', 0),
        ]);
    }
}
