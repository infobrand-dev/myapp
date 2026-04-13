<?php

namespace App\Modules\Finance\Http\Requests;

use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountingPeriodLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('finance.manage-period-locks') : false;
    }

    public function rules(): array
    {
        return [
            'locked_from' => ['required', 'date'],
            'locked_until' => ['required', 'date', 'after_or_equal:locked_from'],
            'notes' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
        ];
    }
}
