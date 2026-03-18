<?php

namespace App\Modules\PointOfSale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePosCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? $this->user()->can('pos.use') : false;
    }

    public function rules(): array
    {
        return [
            'qty' => ['nullable', 'numeric', 'min:0.0001'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
