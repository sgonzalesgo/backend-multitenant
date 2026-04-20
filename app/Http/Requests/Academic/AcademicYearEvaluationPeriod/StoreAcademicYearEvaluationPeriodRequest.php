<?php

namespace App\Http\Requests\Academic\AcademicYearEvaluationPeriod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicYearEvaluationPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academicYearId = $this->input('academic_year_id');

        return [
            'academic_year_id' => [
                'required',
                'uuid',
                Rule::exists('academic_years', 'id')
                    ->whereNull('deleted_at'),
            ],

            'evaluation_period_id' => [
                'required',
                'uuid',
                Rule::exists('evaluation_periods', 'id')
                    ->whereNull('deleted_at'),
                Rule::unique('academic_year_evaluation_periods', 'evaluation_period_id')
                    ->where(function ($query) use ($academicYearId) {
                        return $query
                            ->where('academic_year_id', $academicYearId)
                            ->whereNull('deleted_at');
                    }),
            ],

            'order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('academic_year_evaluation_periods', 'order')
                    ->where(function ($query) use ($academicYearId) {
                        return $query
                            ->where('academic_year_id', $academicYearId)
                            ->whereNull('deleted_at');
                    }),
            ],

            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/academic-year-evaluation-period.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/academic-year-evaluation-period.attributes');
    }
}
