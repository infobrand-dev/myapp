<?php

namespace App\Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin_postal_code' => ['nullable', 'string', 'max:20'],
            'destination_postal_code' => ['nullable', 'string', 'max:20'],
            'origin_area_id' => ['nullable', 'string', 'max:120'],
            'destination_area_id' => ['nullable', 'string', 'max:120'],
            'couriers' => ['nullable', 'string', 'max:500'],
            'item_name' => ['required', 'string', 'max:150'],
            'item_description' => ['nullable', 'string', 'max:500'],
            'item_value' => ['required', 'numeric', 'min:0'],
            'item_weight' => ['required', 'integer', 'min:1'],
            'item_quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'item_length' => ['nullable', 'integer', 'min:1'],
            'item_width' => ['nullable', 'integer', 'min:1'],
            'item_height' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'origin_postal_code' => trim((string) $this->input('origin_postal_code', '')),
            'destination_postal_code' => trim((string) $this->input('destination_postal_code', '')),
            'origin_area_id' => trim((string) $this->input('origin_area_id', '')),
            'destination_area_id' => trim((string) $this->input('destination_area_id', '')),
            'couriers' => trim((string) $this->input('couriers', '')),
            'item_name' => trim((string) $this->input('item_name', '')),
            'item_description' => trim((string) $this->input('item_description', '')),
        ]);
    }
}
