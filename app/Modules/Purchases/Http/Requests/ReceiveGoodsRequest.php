<?php

namespace App\Modules\Purchases\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'inventory_location_id' => ['required', 'integer', 'exists:inventory_locations,id'],
            'receipt_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_item_id' => ['required', 'integer', 'exists:purchase_items,id'],
            'items.*.qty_received' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
