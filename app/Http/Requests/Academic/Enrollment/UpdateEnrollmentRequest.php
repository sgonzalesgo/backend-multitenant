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

            'student_id' => ['nullable', 'uuid', 'exists:students,id'],
            'student' => ['nullable', 'array'],

            'student.person_id' => ['nullable', 'uuid', 'exists:persons,id'],
            'student.person' => ['nullable', 'array'],

            'student.person.legal_id' => ['nullable', 'string', 'max:80'],
            'student.person.legal_id_type' => ['nullable', 'string', 'max:80'],
            'student.person.full_name' => ['nullable', 'string', 'max:255'],
            'student.person.email' => ['nullable', 'email', 'max:255'],
            'student.person.phone' => ['nullable', 'string', 'max:80'],
            'student.person.birthday' => ['nullable', 'date'],
            'student.person.gender' => ['nullable', 'string', 'max:50'],
            'student.person.address' => ['nullable', 'string', 'max:5000'],
            'student.person.country_id' => ['nullable', 'numeric', 'exists:countries,id'],
            'student.person.state_id' => ['nullable', 'numeric', 'exists:states,id'],
            'student.person.city_id' => ['nullable', 'numeric', 'exists:cities,id'],
            'student.person.photo' => ['nullable', 'image', 'max:2048'],
            'student.person.zip' => ['nullable', 'string', 'max:20'],
            'student.person.marital_status' => ['nullable', 'string', 'max:80'],
            'student.person.blood_group' => ['nullable', 'string', 'max:20'],
            'student.person.nationality' => ['nullable', 'string', 'max:120'],

            'student.status' => ['nullable', 'string', 'max:80'],
            'student.notes' => ['nullable', 'string', 'max:5000'],

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

            'representatives.*.legal_representative_id' => ['nullable', 'uuid', 'exists:legal_representatives,id'],
            'representatives.*.legal_representative' => ['nullable', 'array'],

            'representatives.*.legal_representative.person_id' => ['nullable', 'uuid', 'exists:persons,id'],
            'representatives.*.legal_representative.person' => ['nullable', 'array'],

            'representatives.*.legal_representative.person.legal_id' => ['nullable', 'string', 'max:80'],
            'representatives.*.legal_representative.person.legal_id_type' => ['nullable', 'string', 'max:80'],
            'representatives.*.legal_representative.person.full_name' => ['nullable', 'string', 'max:255'],
            'representatives.*.legal_representative.person.email' => ['nullable', 'email', 'max:255'],
            'representatives.*.legal_representative.person.phone' => ['nullable', 'string', 'max:80'],
            'representatives.*.legal_representative.person.birthday' => ['nullable', 'date'],
            'representatives.*.legal_representative.person.gender' => ['nullable', 'string', 'max:50'],
            'representatives.*.legal_representative.person.address' => ['nullable', 'string', 'max:5000'],
            'representatives.*.legal_representative.person.country_id' => ['nullable', 'numeric', 'exists:countries,id'],
            'representatives.*.legal_representative.person.state_id' => ['nullable', 'numeric', 'exists:states,id'],
            'representatives.*.legal_representative.person.city_id' => ['nullable', 'numeric', 'exists:cities,id'],
            'representatives.*.legal_representative.person.photo' => ['nullable', 'image', 'max:2048'],

            'representatives.*.legal_representative.status' => ['nullable', 'string', 'max:80'],
            'representatives.*.legal_representative.notes' => ['nullable', 'string', 'max:5000'],

            'representatives.*.relationship_type' => ['required_with:representatives', 'string', 'max:80'],
            'representatives.*.description' => ['nullable', 'string', 'max:5000'],
            'representatives.*.is_billable' => ['nullable', 'boolean'],
            'representatives.*.is_emergency_contact' => ['nullable', 'boolean'],
            'representatives.*.legal_representative.person.zip' => ['nullable', 'string', 'max:20'],
            'representatives.*.legal_representative.person.marital_status' => ['nullable', 'string', 'max:80'],
            'representatives.*.legal_representative.person.blood_group' => ['nullable', 'string', 'max:20'],
            'representatives.*.legal_representative.person.nationality' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return trans('validation/Academic/enrollment.custom') ?? [];
    }

    public function attributes(): array
    {
        return trans('validation/Academic/enrollment.attributes') ?? [];
    }
}
