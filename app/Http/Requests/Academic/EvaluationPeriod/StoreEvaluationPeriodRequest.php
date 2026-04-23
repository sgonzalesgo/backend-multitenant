<?php

namespace App\Http\Requests\Academic\EvaluationPeriod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'required',
                'uuid',
                'exists:academic_years,id',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('evaluation_periods', 'code')
                    ->where(function ($query) {
                        return $query
                            ->where('academic_year_id', $this->academic_year_id)
                            ->whereNull('deleted_at');
                    }),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('evaluation_periods', 'name')
                    ->where(function ($query) {
                        return $query
                            ->where('academic_year_id', $this->academic_year_id)
                            ->whereNull('deleted_at');
                    }),
            ],

            'description' => ['nullable', 'string', 'max:255'],

            'default_order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('evaluation_periods', 'default_order')
                    ->where(function ($query) {
                        return $query
                            ->where('academic_year_id', $this->academic_year_id)
                            ->whereNull('deleted_at');
                    }),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
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
