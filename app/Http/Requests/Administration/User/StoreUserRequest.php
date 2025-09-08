<?php

namespace App\Http\Requests\Administration\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')],
            'password' => ['required','string','min:8','confirmed'], // expects password_confirmation
            'locale'   => ['sometimes','string','max:10'],
            'avatar'   => ['sometimes','nullable','url','max:2048'],
        ];
    }

    public function messages(): array     { return __('validation/administration/user.custom'); }
    public function attributes(): array   { return __('validation/administration/user.attributes'); }
}
