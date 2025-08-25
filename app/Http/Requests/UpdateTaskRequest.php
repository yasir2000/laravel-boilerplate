<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|required|in:' . implode(',', array_keys(Task::getStatusOptions())),
            'priority' => 'sometimes|required|in:' . implode(',', array_keys(Task::getPriorityOptions())),
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'actual_hours' => 'nullable|numeric|min:0|max:999.99',
            'project_id' => 'sometimes|required|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'task title',
            'description' => 'task description',
            'due_date' => 'due date',
            'estimated_hours' => 'estimated hours',
            'actual_hours' => 'actual hours',
            'project_id' => 'project',
            'assigned_to' => 'assigned user',
        ];
    }
}
