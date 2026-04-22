<?php

namespace App\Modules\Finance\Http\Requests;

use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('finance.manage-reconciliation') : false;
    }

    public function rules(): array
    {
        return [
            'finance_account_id' => ['required', 'integer', Rule::exists('finance_accounts', 'id')->where(function ($query) {
                $query->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId());
            })],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'statement_ending_balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
