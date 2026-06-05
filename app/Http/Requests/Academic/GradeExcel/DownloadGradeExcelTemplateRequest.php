<?php

namespace App\Http\Requests\Academic\GradeExcel;

use Illuminate\Foundation\Http\FormRequest;

class DownloadGradeExcelTemplateRequest extends FormRequest
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
            'educational_level_code' => ['required', 'string'],
            'course_id' => ['required', 'uuid', 'exists:courses,id'],
            'specialty_id' => ['nullable', 'uuid', 'exists:specialties,id'],
            'parallel_id' => ['required', 'uuid', 'exists:parallels,id'],
            'modality_id' => ['required', 'uuid', 'exists:modalities,id'],
            'shift_id' => ['required', 'uuid', 'exists:shifts,id'],

            'subject_id' => ['required', 'uuid', 'exists:subjects,id'],
            'instructor_id' => ['required', 'uuid', 'exists:instructors,id'],
        ];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/Academic/grade_excel_template.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
