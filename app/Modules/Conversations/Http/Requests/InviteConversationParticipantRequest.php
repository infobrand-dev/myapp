<?php

namespace App\Modules\Conversations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteConversationParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string'],
            'role'  => ['nullable', 'string', 'max:50'],
        ];
    }
}
