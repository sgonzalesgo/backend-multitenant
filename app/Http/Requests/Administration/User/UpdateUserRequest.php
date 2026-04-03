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
            'person_id' => [
                'sometimes',
                'nullable',
                'uuid',
                'exists:persons,id',
                Rule::unique('users', 'person_id')->ignore($userId),
            ],
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password'  => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
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
