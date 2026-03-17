<?php

namespace App\Modules\Discounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvaluateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('discounts.evaluate') ?? false;
    }

    public function rules(): array
    {
        return [
            'voucher_code' => ['nullable', 'string', 'max:100'],
            'customer.reference_type' => ['nullable', 'string', 'max:100'],
            'customer.reference_id' => ['nullable', 'string', 'max:100'],
            'customer.group_code' => ['nullable', 'string', 'max:100'],
            'outlet_reference' => ['nullable', 'string', 'max:100'],
            'sales_channel' => ['nullable', 'string', 'max:50'],
            'manual' => ['nullable', 'boolean'],
            'at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.line_key' => ['nullable', 'string', 'max:100'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.category_id' => ['nullable', 'integer'],
            'items.*.brand_id' => ['nullable', 'integer'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.variant_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.subtotal' => ['nullable', 'numeric', 'min:0'],
            'items.*.meta' => ['nullable', 'array'],
        ];
    }
}
