<?php

namespace App\Http\Requests\Academic\GradeSession;

use Illuminate\Foundation\Http\FormRequest;

class SaveGradeSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1'],

            'records.*.grade_record_id' => [
                'required',
                'uuid',
                'exists:grade_records,id',
            ],

            'records.*.components' => [
                'required',
                'array',
                'min:1',
            ],

            'records.*.components.*.grade_record_component_id' => [
                'required',
                'uuid',
                'exists:grade_record_components,id',
            ],

            'records.*.components.*.score' => [
                'nullable',
                'numeric',
                'min:0',
                'max:10',
            ],

            'records.*.components.*.qualitative_grade' => [
                'nullable',
                'string',
                'max:20',
            ],

            'records.*.components.*.observation' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/academic/grade_session.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
