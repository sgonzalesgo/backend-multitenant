<?php

namespace App\Http\Requests\General\Department;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:100'],
            'person_id' => ['sometimes', 'nullable', 'uuid', 'exists:persons,id'],
            'status' => ['sometimes', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        $messages = trans('validation/general/department.custom');

        return is_array($messages) ? $messages : [];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/general/department.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
