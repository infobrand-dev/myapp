<?php

namespace App\Modules\PointOfSale\Http\Requests;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('pos.open-shift') : false;
    }

    public function rules(): array
    {
        return [
            'opening_cash_amount' => ['required', 'numeric', 'min:0'],
            'branch_id' => ['nullable', 'integer', 'min:1', Rule::exists('branches', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
            'opening_note' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'branch_id' => $this->input('branch_id', $this->input('outlet_id', BranchContext::currentId())),
        ]);
    }
}
