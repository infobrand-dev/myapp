<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportOpeningStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.manage-opening-stock') ?? false;
    }

    public function rules(): array
    {
        return [
            'inventory_location_id' => ['required', 'integer', Rule::exists('inventory_locations', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
            'opening_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'import_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ];
    }
}
