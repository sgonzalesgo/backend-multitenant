<?php

namespace App\Http\Requests\Administration\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = is_object($user) ? $user->id : ($user ?? $this->id);

        return [
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password'  => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'locale'    => ['sometimes', 'string', 'max:10'],
            'avatar'    => ['sometimes', 'nullable', 'file', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return __('validation/administration/user.custom');
    }

    public function attributes(): array
    {
        return __('validation/administration/user.attributes');
    }
}
