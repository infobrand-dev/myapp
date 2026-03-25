<?php

namespace App\Modules\Chatbot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatbotKnowledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'      => ['required', 'string', 'max:255'],
            'source'     => ['nullable', 'string', 'max:100'],
            'content'    => ['required', 'string', 'max:200000'],
            'chunk_size' => ['nullable', 'integer', 'min:300', 'max:1200'],
        ];
    }
}
