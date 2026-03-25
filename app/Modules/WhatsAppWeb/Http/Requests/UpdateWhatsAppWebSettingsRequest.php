<?php

namespace App\Modules\WhatsAppWeb\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWhatsAppWebSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_url'     => ['required', 'url'],
            'verify_token' => ['nullable', 'string', 'max:255'],
        ];
    }
}
