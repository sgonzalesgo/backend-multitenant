<?php

namespace App\Http\Requests\Administration\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id' => ['nullable', 'uuid', 'exists:persons,id', 'unique:users,person_id'],
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password'  => ['required', 'string', 'min:8'],
            'status'    => ['sometimes', 'string', 'max:50'],
            'locale'    => ['sometimes', 'string', 'max:10'],
            'avatar'    => ['sometimes', 'nullable', 'file', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        $messages = trans('validation/administration/user.custom');

        return is_array($messages) ? $messages : [];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/administration/user.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
