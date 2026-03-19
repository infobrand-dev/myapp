<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'inventory_location_id' => ['required', 'integer', 'exists:inventory_locations,id'],
            'opname_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
