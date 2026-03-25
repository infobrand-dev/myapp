<?php

namespace App\Modules\LiveChat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLiveChatVisitorMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitor_key'   => ['required', 'string', 'max:100'],
            'visitor_token' => ['required', 'string', 'max:200'],
            'body'          => ['required', 'string', 'max:4000'],
            'visitor_name'  => ['nullable', 'string', 'max:120'],
            'visitor_email' => ['nullable', 'email', 'max:255'],
            'page_url'      => ['nullable', 'url', 'max:500'],
        ];
    }
}
