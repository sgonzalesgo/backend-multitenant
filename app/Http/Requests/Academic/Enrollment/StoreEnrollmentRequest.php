<?php

namespace App\Http\Requests\Academic\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            // Student
            'student_id' => ['nullable', 'uuid', 'exists:students,id'],
            'student' => ['required_without:student_id', 'array'],

            'student.person_id' => ['nullable', 'uuid', 'exists:persons,id'],
            'student.person' => ['required_without:student.person_id', 'array'],

            'student.person.legal_id' => ['required_without:student.person_id', 'string', 'max:80'],
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

            // 🔹 Enrollment
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'course_id' => ['required', 'uuid', 'exists:courses,id'],
            'specialty_id' => ['nullable', 'uuid', 'exists:specialties,id'],
            'parallel_id' => ['required', 'uuid', 'exists:parallels,id'],
            'shift_id' => ['required', 'uuid', 'exists:shifts,id'],
            'modality_id' => ['required', 'uuid', 'exists:modalities,id'],
            'enrollment_status_id' => ['nullable', 'uuid', 'exists:enrollment_statuses,id'],
            'assigned_user_id' => ['nullable', 'uuid', 'exists:users,id'],

            'is_new' => ['nullable', 'boolean'],
            'is_conditional' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'observation' => ['nullable', 'string', 'max:5000'],
            'submitted_at' => ['nullable', 'date'],

            // 🔹 Representatives
            'representatives' => ['required', 'array', 'min:1'],

            'representatives.*.legal_representative_id' => ['nullable', 'uuid', 'exists:legal_representatives,id'],
            'representatives.*.legal_representative' => [
                'required_without:representatives.*.legal_representative_id',
                'array',
            ],

            'representatives.*.legal_representative.person_id' => ['nullable', 'uuid', 'exists:persons,id'],
            'representatives.*.legal_representative.person' => [
                'required_without:representatives.*.legal_representative.person_id',
                'array',
            ],

            'representatives.*.legal_representative.person.legal_id' => [
                'required_without:representatives.*.legal_representative.person_id',
                'string',
                'max:80',
            ],
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
            'representatives.*.legal_representative.person.zip' => ['nullable', 'string', 'max:20'],
            'representatives.*.legal_representative.person.marital_status' => ['nullable', 'string', 'max:80'],
            'representatives.*.legal_representative.person.blood_group' => ['nullable', 'string', 'max:20'],
            'representatives.*.legal_representative.person.nationality' => ['nullable', 'string', 'max:120'],

            'representatives.*.legal_representative.status' => ['nullable', 'string', 'max:80'],
            'representatives.*.legal_representative.notes' => ['nullable', 'string', 'max:5000'],

            'representatives.*.relationship_type' => ['required', 'string', 'max:80'],
            'representatives.*.description' => ['nullable', 'string', 'max:5000'],
            'representatives.*.is_billable' => ['nullable', 'boolean'],
            'representatives.*.is_emergency_contact' => ['nullable', 'boolean'],
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
