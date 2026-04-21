<?php

namespace App\Http\Requests\Academic\EvaluationPeriod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEvaluationPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $evaluationPeriod = $this->route('evaluationPeriod');

        if (is_object($evaluationPeriod)) {
            $evaluationPeriod = $evaluationPeriod->id;
        }

        $academicYearId = $this->input('academic_year_id');

        if (!$academicYearId && is_object($this->route('evaluationPeriod'))) {
            $academicYearId = $this->route('evaluationPeriod')->academic_year_id;
        }

        return [
            'academic_year_id' => [
                'sometimes',
                'required',
                'uuid',
                'exists:academic_years,id',
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('evaluation_periods', 'code')
                    ->ignore($evaluationPeriod)
                    ->where(function ($query) use ($academicYearId) {
                        return $query
                            ->where('academic_year_id', $academicYearId)
                            ->whereNull('deleted_at');
                    }),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('evaluation_periods', 'name')
                    ->ignore($evaluationPeriod)
                    ->where(function ($query) use ($academicYearId) {
                        return $query
                            ->where('academic_year_id', $academicYearId)
                            ->whereNull('deleted_at');
                    }),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'default_order' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('evaluation_periods', 'default_order')
                    ->ignore($evaluationPeriod)
                    ->where(function ($query) use ($academicYearId) {
                        return $query
                            ->where('academic_year_id', $academicYearId)
                            ->whereNull('deleted_at');
                    }),
            ],
            'start_date' => [
                'sometimes',
                'required',
                'date',
            ],
            'end_date' => [
                'sometimes',
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/evaluation-period.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/evaluation-period.attributes');
    }
}
