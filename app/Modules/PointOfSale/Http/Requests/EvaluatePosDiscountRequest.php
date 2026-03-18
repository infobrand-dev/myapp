<?php

namespace App\Modules\PointOfSale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvaluatePosDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('pos.use') : false;
    }

    public function rules(): array
    {
        return [
            'voucher_code' => ['nullable', 'string', 'max:100'],
            'customer.reference_type' => ['nullable', 'string', 'max:100'],
            'customer.reference_id' => ['nullable', 'string', 'max:100'],
            'sales_channel' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.line_key' => ['required', 'string', 'max:100'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.subtotal' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
