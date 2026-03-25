<?php

namespace App\Modules\LiveChat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplyLiveChatRequest extends FormRequest
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
