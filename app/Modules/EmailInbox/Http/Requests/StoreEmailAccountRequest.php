<?php

namespace App\Modules\EmailInbox\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email_address' => ['required', 'email', 'max:190'],
            'provider' => ['nullable', 'string', 'max:60'],
            'direction_mode' => ['required', 'in:inbound,outbound,inbound_outbound'],
            'inbound_protocol' => ['nullable', 'in:imap'],
            'inbound_host' => ['nullable', 'string', 'max:190'],
            'inbound_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'inbound_encryption' => ['nullable', 'in:ssl,tls,none'],
            'inbound_username' => ['nullable', 'string', 'max:190'],
            'inbound_password' => ['nullable', 'string', 'max:190'],
            'inbound_validate_cert' => ['nullable', 'boolean'],
            'outbound_host' => ['nullable', 'string', 'max:190'],
            'outbound_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'outbound_encryption' => ['nullable', 'in:ssl,tls,none'],
            'outbound_username' => ['nullable', 'string', 'max:190'],
            'outbound_password' => ['nullable', 'string', 'max:190'],
            'outbound_from_name' => ['nullable', 'string', 'max:120'],
            'outbound_reply_to' => ['nullable', 'email', 'max:190'],
            'sync_enabled' => ['nullable', 'boolean'],
        ];
    }
}
