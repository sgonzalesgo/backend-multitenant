<?php

namespace App\Http\Requests\Administration\position;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')->ignore($id, 'id'),
            ],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('positions', 'code')->ignore($id, 'id'),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function attributes(): array
    {
        return __('validation/administration/position.validation.attributes');
    }

    public function messages(): array
    {
        return __('validation/administration/position.validation.custom');
    }
}
