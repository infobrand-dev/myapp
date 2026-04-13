<?php

namespace App\Modules\Payments\Http\Requests;

class UpdatePaymentRequest extends StorePaymentRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('payments.create') : false;
    }
}
