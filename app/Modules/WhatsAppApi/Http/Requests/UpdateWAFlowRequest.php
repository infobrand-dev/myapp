<?php

namespace App\Modules\WhatsAppApi\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWAFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = TenantContext::currentId();

        return [
            'instance_id'  => ['required', Rule::exists('whatsapp_instances', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId))],
            'name'         => ['required', 'string', 'max:255'],
            'categories'   => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string'],
            'endpoint_uri' => ['nullable', 'url', 'max:255'],
            'flow_json'    => ['nullable', 'string'],
        ];
    }
}
