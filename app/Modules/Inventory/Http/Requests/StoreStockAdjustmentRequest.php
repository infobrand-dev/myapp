<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.manage-stock-adjustment') ?? false;
    }

    public function rules(): array
    {
        return [
            'inventory_location_id' => ['required', 'integer', 'exists:inventory_locations,id'],
            'adjustment_date' => ['required', 'date'],
            'reason_code' => ['required', 'string', 'max:100'],
            'reason_text' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.direction' => ['required', Rule::in(['in', 'out'])],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.movement_type' => ['nullable', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
