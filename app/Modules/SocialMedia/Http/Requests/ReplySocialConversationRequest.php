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
            'body' => ['nullable', 'string', 'max:4000', 'required_without:media_file'],
            'media_file' => ['nullable', 'file', 'max:20480', 'required_without:body'],
        ];
    }
}
