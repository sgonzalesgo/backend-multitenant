<?php

namespace App\Http\Requests\Academic\QualitativeEvaluationSession;

use Illuminate\Foundation\Http\FormRequest;

class IndexQualitativeEvaluationSessionRequest extends FormRequest
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
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
            'specialty_id' => ['nullable', 'uuid', 'exists:specialties,id'],
            'parallel_id' => ['nullable', 'uuid', 'exists:parallels,id'],
            'modality_id' => ['nullable', 'uuid', 'exists:modalities,id'],
            'shift_id' => ['nullable', 'uuid', 'exists:shifts,id'],
            'subject_id' => ['nullable', 'uuid', 'exists:subjects,id'],
            'instructor_id' => ['nullable', 'uuid', 'exists:instructors,id'],

            'status' => ['nullable', 'string', 'in:open,closed'],
            'q' => ['nullable', 'string', 'max:255'],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
