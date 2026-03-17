<?php

namespace App\Modules\Payments\Http\Requests;

use App\Modules\Payments\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class VoidPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var Payment|null $payment */
        $payment = $this->route('payment');

        if (!$user || !$payment) {
            return false;
        }

        return $user->can('void', $payment);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string'],
        ];
    }
}
