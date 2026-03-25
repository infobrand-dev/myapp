<?php

namespace App\Modules\WhatsAppWeb\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InboundWhatsAppWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'               => ['required', 'string'],
            'contact_id'          => ['required', 'string'],
            'contact_name'        => ['nullable', 'string'],
            'message'             => ['required', 'string'],
            'external_message_id' => ['nullable', 'string'],
            'direction'           => ['nullable', 'in:in,out'],
            'client_id'           => ['nullable', 'string', 'max:100'],
            'type'                => ['nullable', 'string', 'max:50'],
            'author'              => ['nullable', 'string'],
            'occurred_at'         => ['nullable', 'date'],
        ];
    }
}
