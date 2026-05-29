<?php

namespace App\Modules\Xendit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateXenditSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'environment' => ['required', 'in:sandbox,production'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'webhook_token' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
