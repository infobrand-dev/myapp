<?php

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Sales\Models\SaleItem;
use App\Modules\Sales\Models\Sale;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;

class StoreSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales_return.create') : false;
    }

    public function rules(): array
    {
        $inventoryLocationRules = ['nullable', 'integer', 'min:1'];

        if (Schema::hasTable('inventory_locations')) {
            $inventoryLocationRules[] = Rule::exists('inventory_locations', 'id')->where(fn ($query) => BranchContext::applyScope($query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())));
        }

        return [
            'sale_id' => ['required', 'integer', Rule::exists('sales', 'id')->where(fn ($query) => BranchContext::applyScope($query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->where('status', Sale::STATUS_FINALIZED)))],
            'return_date' => ['nullable', 'date'],
            'reason' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'refund_required' => ['nullable', 'boolean'],
            'inventory_restock_required' => ['nullable', 'boolean'],
            'inventory_location_id' => $inventoryLocationRules,
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer', Rule::exists('sale_items', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'items.*.qty_returned' => ['nullable', 'numeric', 'gte:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateTenantRelations($validator),
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => [
                'sale_item_id' => $item['sale_item_id'] ?? null,
                'qty_returned' => $item['qty_returned'] ?? 0,
                'notes' => $item['notes'] ?? null,
            ])
            ->values()
            ->all();

        $this->merge([
            'refund_required' => $this->boolean('refund_required'),
            'inventory_restock_required' => $this->boolean('inventory_restock_required'),
            'items' => $items,
        ]);
    }

    private function validateTenantRelations(Validator $validator): void
    {
        $saleId = $this->input('sale_id');
        if ($saleId && !Sale::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('status', Sale::STATUS_FINALIZED)
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->find($saleId)
        ) {
            $validator->errors()->add('sale_id', 'Sale tidak tersedia untuk scope branch aktif.');
        }

        $locationId = $this->input('inventory_location_id');
        if ($locationId && !InventoryLocation::query()->where('tenant_id', TenantContext::currentId())->where('company_id', CompanyContext::currentId())->tap(fn ($query) => BranchContext::applyScope($query))->find($locationId)) {
            $validator->errors()->add('inventory_location_id', 'Lokasi inventory tidak tersedia untuk tenant aktif.');
        }

        foreach ((array) $this->input('items', []) as $index => $item) {
            $saleItemId = $item['sale_item_id'] ?? null;
            if ($saleItemId && !SaleItem::query()->where('tenant_id', TenantContext::currentId())->find($saleItemId)) {
                $validator->errors()->add("items.$index.sale_item_id", 'Item sale tidak tersedia untuk tenant aktif.');
            }
        }
    }
}
