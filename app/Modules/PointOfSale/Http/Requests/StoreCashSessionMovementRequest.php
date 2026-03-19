<?php

namespace App\Modules\PointOfSale\Http\Requests;

use App\Modules\PointOfSale\Models\PosCashSessionMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashSessionMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('pos.record-cash-movement') : false;
    }

    public function rules(): array
    {
        return [
            'movement_type' => ['required', Rule::in([
                PosCashSessionMovement::TYPE_CASH_IN,
                PosCashSessionMovement::TYPE_CASH_OUT,
            ])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
