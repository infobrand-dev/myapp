<?php

namespace App\Modules\TaskManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tasks' => ['array'],
            'tasks.*.title' => ['required_with:tasks', 'string', 'max:255'],
            'tasks.*.subtasks' => ['array'],
            'tasks.*.subtasks.*.title' => ['required_with:tasks.*.subtasks', 'string', 'max:255'],
        ];
    }
}
