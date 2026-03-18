<?php

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('sales_return.finalize') : false;
    }

    public function rules(): array
    {
        return [];
    }
}
