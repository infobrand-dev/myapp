<?php

namespace App\Modules\TaskManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemoTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string'],
            'status'      => ['required', 'in:pending,in_progress,done'],
            'due_date'    => ['nullable', 'date'],
        ];
    }
}
