<?php

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoidSalePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('payments.void') : false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string'],
        ];
    }
}
