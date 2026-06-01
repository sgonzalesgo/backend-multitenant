<?php

namespace App\Http\Requests\Academic\GradeComponentTemplate;

use Illuminate\Foundation\Http\FormRequest;

class GradeComponentTemplateIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'uuid', 'exists:academic_years,id'],
            'evaluation_period_id' => ['nullable', 'uuid', 'exists:evaluation_periods,id'],
            'educational_level_id' => ['nullable', 'uuid', 'exists:educational_levels,id'],
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
            'specialty_id' => ['nullable', 'uuid', 'exists:specialties,id'],
            'modality_id' => ['nullable', 'uuid', 'exists:modalities,id'],
            'shift_id' => ['nullable', 'uuid', 'exists:shifts,id'],

            'grading_mode' => ['nullable', 'string', 'in:basic_100,mixed_70_30,qualitative'],
            'is_active' => ['nullable', 'boolean'],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/Academic/grade_component_template.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
