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

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('evaluation_periods', 'code')
                    ->ignore($evaluationPeriod)
                    ->whereNull('deleted_at'),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('evaluation_periods', 'name')
                    ->ignore($evaluationPeriod)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'default_order' => ['sometimes', 'required', 'integer', 'min:1'],
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
