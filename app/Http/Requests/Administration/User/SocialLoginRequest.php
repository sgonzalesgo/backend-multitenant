<?php

namespace App\Http\Requests\Administration\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::in(['google','facebook'])],
            // Para Google normalmente es id_token; para Facebook, access_token.
            'token'    => ['required','string','min:10'],
        ];
    }

    public function messages(): array     { return __('validation/auth/social.custom'); }
    public function attributes(): array   { return __('validation/auth/social.attributes'); }
}
