<?php

namespace App\Http\Requests\Academic\GradeComponentTemplate;

use Illuminate\Foundation\Http\FormRequest;

class GenerateGradeComponentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'evaluation_period_id' => ['required', 'uuid', 'exists:evaluation_periods,id'],

            'course_id' => ['required', 'uuid', 'exists:courses,id'],
            'specialty_id' => ['nullable', 'uuid', 'exists:specialties,id'],
            'modality_id' => ['nullable', 'uuid', 'exists:modalities,id'],
            'shift_id' => ['nullable', 'uuid', 'exists:shifts,id'],

            'parallel_ids' => ['required', 'array', 'min:1'],
            'parallel_ids.*' => ['required', 'uuid', 'exists:parallels,id'],

            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['required', 'uuid', 'exists:subjects,id'],
        ];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/Academic/grade_component_template.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
