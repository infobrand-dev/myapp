<?php

namespace App\Modules\LiveChat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LiveChatTypingRequest extends FormRequest
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
        ];
    }
}
