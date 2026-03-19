<?php

namespace App\Modules\PointOfSale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('pos.close-shift') : false;
    }

    public function rules(): array
    {
        return [
            'closing_cash_amount' => ['required', 'numeric', 'min:0'],
            'closing_note' => ['nullable', 'string'],
        ];
    }
}
