<?php

namespace App\Http\Requests\Academic\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['sometimes', 'required', 'uuid', 'exists:students,id'],
            'academic_year_id' => ['sometimes', 'required', 'uuid', 'exists:academic_years,id'],
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
            'parallel_id' => ['nullable', 'uuid', 'exists:parallels,id'],
            'shift_id' => ['nullable', 'uuid', 'exists:shifts,id'],
            'enrollment_status_id' => ['nullable', 'uuid', 'exists:enrollment_statuses,id'],
            'assigned_user_id' => ['nullable', 'uuid', 'exists:users,id'],

            'is_new' => ['nullable', 'boolean'],
            'is_conditional' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'observation' => ['nullable', 'string', 'max:5000'],
            'submitted_at' => ['nullable', 'date'],

            'representatives' => ['nullable', 'array', 'min:1'],
            'representatives.*.legal_representative_id' => ['required_with:representatives', 'uuid', 'exists:legal_representatives,id'],
            'representatives.*.relationship_type' => ['required_with:representatives', 'string', 'max:80'],
            'representatives.*.description' => ['nullable', 'string', 'max:5000'],
            'representatives.*.is_billable' => ['nullable', 'boolean'],
            'representatives.*.is_emergency_contact' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        $messages = trans('validation/Academic/enrollment.custom');

        return is_array($messages) ? $messages : [];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/Academic/enrollment.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
