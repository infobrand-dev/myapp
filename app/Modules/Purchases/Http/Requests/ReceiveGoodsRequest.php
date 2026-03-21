<?php

namespace App\Modules\Purchases\Http\Requests;

use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Purchases\Models\PurchaseItem;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReceiveGoodsRequest extends FormRequest
{

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => [
                'purchase_item_id' => isset($item['purchase_item_id']) ? (int) $item['purchase_item_id'] : null,
                'qty_received' => ($item['qty_received'] ?? '') === '' ? 0 : $item['qty_received'],
            ])
            ->values()
            ->all();

        $this->merge([
            'inventory_location_id' => $this->filled('inventory_location_id') ? (int) $this->input('inventory_location_id') : null,
            'items' => $items,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('purchases.receive') : false;
    }

    public function rules(): array
    {
        return [
            'inventory_location_id' => ['required', 'integer', Rule::exists('inventory_locations', 'id')->where(fn ($query) => BranchContext::applyScope($query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())))],
            'receipt_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['required', 'integer', Rule::exists('purchase_items', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'items.*.qty_received' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateTenantRelations($validator),
        ];
    }

    private function validateTenantRelations(Validator $validator): void
    {
        $locationId = $this->input('inventory_location_id');
        if ($locationId && !InventoryLocation::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->tap(fn ($query) => BranchContext::applyScope($query))
            ->find($locationId)
        ) {
            $validator->errors()->add('inventory_location_id', 'Lokasi inventory tidak tersedia untuk tenant aktif.');
        }

        foreach ((array) $this->input('items', []) as $index => $item) {
            $purchaseItemId = $item['purchase_item_id'] ?? null;
            if ($purchaseItemId && !PurchaseItem::query()->where('tenant_id', TenantContext::currentId())->find($purchaseItemId)) {
                $validator->errors()->add("items.$index.purchase_item_id", 'Item purchase tidak tersedia untuk tenant aktif.');
            }
        }
    }
}
