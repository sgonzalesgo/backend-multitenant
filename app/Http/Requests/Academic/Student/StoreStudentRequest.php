<?php

namespace App\Http\Requests\Academic\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Person
            'full_name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],

            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],

            'zip' => ['nullable', 'string', 'max:30'],
            'legal_id' => ['required', 'string', 'max:50'],
            'legal_id_type' => ['required', 'string', 'max:50'],

            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:30'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'nationality' => ['nullable', 'string', 'max:120'],
            'deceased_at' => ['nullable', 'date'],
            'status_changed_at' => ['nullable', 'date'],

            // Student
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:5000'],

            // User
            'has_user' => ['nullable', 'boolean'],
            'user_name' => ['nullable', 'required_if:has_user,true,1', 'string', 'max:255'],
            'user_email' => [
                'nullable',
                'required_if:has_user,true,1',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'user_password' => ['nullable', 'required_if:has_user,true,1', 'confirmed', Password::defaults()],
            'user_status' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        $messages = trans('validation/Academic/student.custom');

        return is_array($messages) ? $messages : [];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/Academic/student.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
