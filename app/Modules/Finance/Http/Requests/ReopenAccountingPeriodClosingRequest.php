<?php

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReopenAccountingPeriodClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('finance.manage-period-locks') : false;
    }

    public function rules(): array
    {
        return [
            'entry_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
