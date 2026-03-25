<?php

namespace App\Modules\LiveChat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BootstrapLiveChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitor_key'   => ['nullable', 'string', 'max:100'],
            'visitor_token' => ['nullable', 'string', 'max:200'],
            'visitor_name'  => ['nullable', 'string', 'max:120'],
            'visitor_email' => ['nullable', 'email', 'max:255'],
            'page_url'      => ['nullable', 'url', 'max:500'],
        ];
    }
}
