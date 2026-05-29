<?php

namespace App\Modules\Biteship\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBiteshipSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'environment' => ['required', 'in:sandbox,production'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'default_couriers' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}
