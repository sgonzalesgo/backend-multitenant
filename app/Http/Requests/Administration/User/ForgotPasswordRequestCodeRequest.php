<?php

namespace App\Http\Requests\Administration\User;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequestCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return __('validation/auth/forgot_password_request_code.custom');
    }

    public function attributes(): array
    {
        return __('validation/auth/forgot_password_request_code.attributes');
    }
}
