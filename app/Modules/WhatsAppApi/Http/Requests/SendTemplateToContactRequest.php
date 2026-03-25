<?php

namespace App\Modules\WhatsAppApi\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendTemplateToContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = TenantContext::currentId();

        return [
            'contact_id'   => ['required', 'integer'],
            'instance_id'  => ['required', 'integer'],
            'template_id'  => ['required', 'integer', Rule::exists('wa_templates', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'variables'    => ['nullable', 'array'],
            'variables.*'  => ['nullable', 'string', 'max:500'],
            'return_to'    => ['nullable', 'url'],
        ];
    }
}
