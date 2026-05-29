<?php

namespace App\Modules\RajaOngkir\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRajaOngkirSettingsRequest extends FormRequest
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
            'default_origin_area_id' => ['nullable', 'string', 'max:120'],
            'default_couriers' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}
