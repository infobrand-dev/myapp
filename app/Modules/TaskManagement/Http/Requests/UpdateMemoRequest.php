<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'account_executive' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'tasks' => ['array'],
            'tasks.*.title' => ['required_with:tasks', 'string', 'max:255'],
            'tasks.*.description' => ['nullable', 'string'],
            'tasks.*.due_date' => ['nullable', 'date'],
            'tasks.*.pic' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'tasks.*.subtasks' => ['array'],
            'tasks.*.subtasks.*.title' => ['required_with:tasks.*.subtasks', 'string', 'max:255'],
            'tasks.*.subtasks.*.pic' => ['nullable', 'string', 'max:255'],
            'tasks.*.subtasks.*.due_date' => ['nullable', 'date'],
        ];
    }
}
