<?php

namespace App\Modules\PointOfSale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePosCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? $this->user()->can('pos.use') : false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'qty' => ['nullable', 'numeric', 'min:0.0001'],
            'barcode_scanned' => ['nullable', 'string', 'max:100'],
        ];
    }
}
