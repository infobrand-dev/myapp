<?php

namespace App\Modules\Finance\Http\Requests;

use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalMatrixRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('finance.approve-sensitive-transactions') : false;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(function ($query) {
                $query->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId());
            })],
            'module' => ['required', 'string', 'max:50'],
            'action' => ['required', 'string', 'max:80'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'required_approvals' => ['required', 'integer', 'min:1', 'max:9'],
            'maker_checker_required' => ['nullable', 'boolean'],
            'max_backdate_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
