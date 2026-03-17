<?php

namespace App\Modules\Payments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoidPaymentRequest extends FormRequest
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
