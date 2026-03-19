<?php

namespace App\Modules\PointOfSale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('pos.open-shift') : false;
    }

    public function rules(): array
    {
        return [
            'opening_cash_amount' => ['required', 'numeric', 'min:0'],
            'outlet_id' => ['nullable', 'integer', 'min:1'],
            'opening_note' => ['nullable', 'string'],
        ];
    }
}
