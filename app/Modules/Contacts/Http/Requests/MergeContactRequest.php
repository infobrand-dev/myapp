<?php

namespace App\Modules\Contacts\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'primary_id' => ['required', 'integer', Rule::exists('contacts', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'duplicate_ids' => ['required', 'array', 'min:1'],
            'duplicate_ids.*' => ['integer', 'distinct', Rule::exists('contacts', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
        ];
    }
}
