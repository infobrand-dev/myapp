<?php

namespace App\Modules\Purchases\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoidPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('purchases.void') : false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string'],
        ];
    }
}
