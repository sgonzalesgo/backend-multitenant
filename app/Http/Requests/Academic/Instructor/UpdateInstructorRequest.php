<?php

namespace App\Http\Requests\Academic\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Person
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:30'],
            'legal_id' => ['sometimes', 'required', 'string', 'max:50'],
            'legal_id_type' => ['sometimes', 'required', 'string', 'max:50'],
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:30'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'nationality' => ['nullable', 'string', 'max:120'],
            'person_status' => ['nullable', 'string', 'max:50'],
            'deceased_at' => ['nullable', 'date'],
            'person_status_changed_at' => ['nullable', 'date'],

            // Instructor
            'code' => ['nullable', 'string', 'max:100'],
            'academic_title' => ['nullable', 'string', 'max:100'],
            'academic_level' => ['nullable', 'string', 'max:100'],
            'specialty' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:50'],
            'status_changed_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/instructor.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/instructor.attributes');
    }
}
