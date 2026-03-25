<?php

namespace App\Modules\WhatsAppApi\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = TenantContext::currentId();

        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'role'    => ['nullable', 'string', 'max:50'],
        ];
    }
}
