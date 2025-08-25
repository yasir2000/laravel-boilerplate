<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:' . implode(',', array_keys(Task::getStatusOptions())),
            'priority' => 'required|in:' . implode(',', array_keys(Task::getPriorityOptions())),
            'due_date' => 'nullable|date|after:today',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'project_id' => 'required|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (auth()->check()) {
            $this->merge([
                'created_by' => auth()->id(),
            ]);
        }
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
            'project_id' => 'project',
            'assigned_to' => 'assigned user',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'due_date.after' => 'The due date must be a future date.',
        ];
    }
}
