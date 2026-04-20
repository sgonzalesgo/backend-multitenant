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
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('evaluation_periods', 'code')
                    ->whereNull('deleted_at'),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('evaluation_periods', 'name')
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'default_order' => ['required', 'integer', 'min:1'],
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
