<?php

namespace App\Modules\TaskManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'due_date' => ['nullable', 'date'],
            'subtasks' => ['array'],
            'subtasks.*.title' => ['required_with:subtasks', 'string', 'max:255'],
            'subtasks.*.pic' => ['nullable', 'string', 'max:255'],
            'subtasks.*.due_date' => ['nullable', 'date'],
        ];
    }
}
