<?php

namespace App\Modules\PointOfSale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HoldPosCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? $this->user()->can('pos.hold-cart') : false;
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
