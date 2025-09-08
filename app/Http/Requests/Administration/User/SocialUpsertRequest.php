<?php

namespace App\Http\Requests\Administration\User;

use Illuminate\Foundation\Http\FormRequest;

class SocialUpsertRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'provider'      => ['required', Rule::in(['google','facebook'])],
            'access_token'  => ['required','string','min:10'],
            // opcionales para “hints” si el provider no trae algo
            'email'         => ['sometimes','nullable','email','max:255'],
            'name'          => ['sometimes','nullable','string','max:255'],
            'avatar'        => ['sometimes','nullable','url','max:2048'],
            'locale'        => ['sometimes','nullable','string','max:10'],
        ];
    }

    public function messages(): array
    {
        return __('validation/auth/social_upsert.custom');
    }

    public function attributes(): array
    {
        return __('validation/auth/social_upsert.attributes');
    }
}
