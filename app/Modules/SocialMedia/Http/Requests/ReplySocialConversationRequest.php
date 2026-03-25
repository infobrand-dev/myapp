<?php

namespace App\Modules\SocialMedia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplySocialConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:4000'],
        ];
    }
}
