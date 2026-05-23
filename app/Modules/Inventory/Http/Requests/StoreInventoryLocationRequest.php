<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.manage-locations') ?? false;
    }

    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('inventory_locations', 'id')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', TenantContext::currentId())
                        ->where('company_id', CompanyContext::currentId())
                        ->where('branch_id', BranchContext::currentId())),
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('inventory_locations', 'code')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', TenantContext::currentId())
                        ->where('company_id', CompanyContext::currentId())
                        ->where('branch_id', BranchContext::currentId())),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['warehouse', 'storefront', 'staging', 'returns'])],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
