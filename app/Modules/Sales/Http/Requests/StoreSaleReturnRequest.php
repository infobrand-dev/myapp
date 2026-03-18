<?php

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

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
            $inventoryLocationRules[] = 'exists:inventory_locations,id';
        }

        return [
            'sale_id' => ['required', 'integer', Rule::exists('sales', 'id')->where('status', Sale::STATUS_FINALIZED)],
            'return_date' => ['nullable', 'date'],
            'reason' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'refund_required' => ['nullable', 'boolean'],
            'inventory_restock_required' => ['nullable', 'boolean'],
            'inventory_location_id' => $inventoryLocationRules,
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer', 'exists:sale_items,id'],
            'items.*.qty_returned' => ['nullable', 'numeric', 'gte:0'],
            'items.*.notes' => ['nullable', 'string'],
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
}
