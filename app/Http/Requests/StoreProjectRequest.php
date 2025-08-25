<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:' . implode(',', array_keys(Project::getStatusOptions())),
            'priority' => 'required|in:' . implode(',', array_keys(Project::getPriorityOptions())),
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'nullable|numeric|min:0|max:999999999.99',
            'company_id' => 'required|exists:companies,id',
            'owner_id' => 'required|exists:users,id',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'project name',
            'description' => 'project description',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'budget' => 'project budget',
            'company_id' => 'company',
            'owner_id' => 'project owner',
            'client_name' => 'client name',
            'client_email' => 'client email',
            'progress_percentage' => 'progress percentage',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'end_date.after' => 'The end date must be after the start date.',
            'start_date.after_or_equal' => 'The start date must be today or a future date.',
        ];
    }
}
