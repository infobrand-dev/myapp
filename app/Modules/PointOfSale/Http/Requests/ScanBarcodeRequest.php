<?php

namespace App\Modules\PointOfSale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? $this->user()->can('pos.use') : false;
    }

    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'max:100'],
        ];
    }
}
