<?php

namespace App\Modules\EmailInbox\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMailboxMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'to' => ['required', 'string', 'max:500'],
            'cc' => ['nullable', 'string', 'max:500'],
            'bcc' => ['nullable', 'string', 'max:500'],
            'subject' => ['required', 'string', 'max:190'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
        ];
    }
}
