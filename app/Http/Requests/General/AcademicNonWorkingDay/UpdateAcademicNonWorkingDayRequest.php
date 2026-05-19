<?php

namespace App\Http\Requests\General\AcademicNonWorkingDay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicNonWorkingDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'uuid', 'exists:academic_years,id'],

            'date' => ['sometimes', 'required', 'date'],
            'name' => ['sometimes', 'required', 'string', 'max:180'],

            'type' => [
                'sometimes',
                'required',
                'string',
                Rule::in([
                    'holiday',
                    'suspension',
                    'emergency',
                    'institutional',
                    'other',
                ]),
            ],

            'affects_attendance' => ['sometimes', 'boolean'],
            'affects_calendar' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],

            'observation' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        $messages = trans('validation/general/academic_non_working_day.custom');

        return is_array($messages)
            ? $messages
            : [];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/general/academic_non_working_day.attributes');

        return is_array($attributes)
            ? $attributes
            : [];
    }
}
