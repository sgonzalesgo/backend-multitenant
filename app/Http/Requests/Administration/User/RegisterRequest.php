<?php

namespace App\Http\Requests\Administration\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id' => ['nullable', 'uuid', 'exists:persons,id', 'unique:users,person_id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'avatar' => ['sometimes', 'nullable', 'file', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        $messages = trans('validation/auth/register.custom');

        return is_array($messages) ? $messages : [];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/auth/register.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
