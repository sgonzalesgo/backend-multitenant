<?php

namespace App\Http\Requests\General\Person;

use Illuminate\Foundation\Http\FormRequest;

class LookupPersonByLegalIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legal_id' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return __('validation/general/person.custom');
    }

    public function attributes(): array
    {
        return __('validation/general/person.attributes');
    }
}
