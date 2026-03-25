<?php

namespace App\Modules\Contacts\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkDestroyContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('contacts', 'id')->where(fn ($q) => $q->where('tenant_id', TenantContext::currentId()))],
        ];
    }
}
