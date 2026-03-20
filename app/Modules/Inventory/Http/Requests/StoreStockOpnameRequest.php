<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Models\InventoryLocation;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStockOpnameRequest extends FormRequest
{

    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('inventory.manage-stock-opname') : false;
    }

    public function rules(): array
    {
        return [
            'inventory_location_id' => ['required', 'integer', Rule::exists('inventory_locations', 'id')->where(fn ($query) => BranchContext::applyScope($query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())))],
            'opname_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $locationId = $this->input('inventory_location_id');
                if ($locationId && !InventoryLocation::query()->where('tenant_id', TenantContext::currentId())->where('company_id', CompanyContext::currentId())->tap(fn ($query) => BranchContext::applyScope($query))->find($locationId)) {
                    $validator->errors()->add('inventory_location_id', 'Lokasi inventory tidak valid untuk tenant aktif.');
                }
            },
        ];
    }
}
