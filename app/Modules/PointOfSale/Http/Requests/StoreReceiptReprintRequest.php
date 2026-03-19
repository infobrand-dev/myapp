<?php

namespace App\Modules\PointOfSale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptReprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('pos.reprint-receipt') : false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason', '')),
        ]);
    }
}
