<?php

namespace App\Modules\PointOfSale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActiveCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? $this->user()->can('pos.use') : false;
    }

    public function rules(): array
    {
        return [
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'customer_label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
