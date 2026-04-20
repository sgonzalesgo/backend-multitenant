<?php

namespace App\Http\Requests\Academic\AcademicYearEvaluationPeriod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicYearEvaluationPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academicYearEvaluationPeriod = $this->route('academicYearEvaluationPeriod');

        if (is_object($academicYearEvaluationPeriod)) {
            $currentId = $academicYearEvaluationPeriod->id;
            $academicYearId = $academicYearEvaluationPeriod->academic_year_id;
        } else {
            $currentId = $academicYearEvaluationPeriod;
            $academicYearId = null;
        }

        return [
            'evaluation_period_id' => [
                'sometimes',
                'required',
                'uuid',
                Rule::exists('evaluation_periods', 'id')
                    ->whereNull('deleted_at'),
                Rule::unique('academic_year_evaluation_periods', 'evaluation_period_id')
                    ->ignore($currentId)
                    ->where(function ($query) use ($academicYearId) {
                        return $query
                            ->where('academic_year_id', $academicYearId)
                            ->whereNull('deleted_at');
                    }),
            ],

            'order' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('academic_year_evaluation_periods', 'order')
                    ->ignore($currentId)
                    ->where(function ($query) use ($academicYearId) {
                        return $query
                            ->where('academic_year_id', $academicYearId)
                            ->whereNull('deleted_at');
                    }),
            ],

            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $academicYearEvaluationPeriod = $this->route('academicYearEvaluationPeriod');

            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');

            if (is_object($academicYearEvaluationPeriod)) {
                $startDate = $startDate ?? optional($academicYearEvaluationPeriod->start_date)->format('Y-m-d');
                $endDate = $endDate ?? optional($academicYearEvaluationPeriod->end_date)->format('Y-m-d');
            }

            if ($startDate && $endDate && $endDate <= $startDate) {
                $validator->errors()->add(
                    'end_date',
                    __('validation.after', [
                        'attribute' => __('validation/academic/academic-year-evaluation-period.attributes.end_date'),
                        'date' => __('validation/academic/academic-year-evaluation-period.attributes.start_date'),
                    ])
                );
            }
        });
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
