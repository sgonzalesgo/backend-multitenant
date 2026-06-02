<?php

namespace App\Http\Requests\Academic\QualitativeEvaluation\QualitativeEvaluationComponent;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateQualitativeEvaluationComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function resolveCurrentTenantId(): ?string
    {
        if ($current = Tenant::current()) {
            return (string) $current->id;
        }

        $user = auth()->user();

        if (! $user || ! method_exists($user, 'token')) {
            return null;
        }

        $token = $user->token();

        return $token && ! empty($token->tenant_id)
            ? (string) $token->tenant_id
            : null;
    }

    public function rules(): array
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.qualitative_evaluation_components.tenant_not_resolved'));
        }

        return [
            'academic_year_id' => ['required', 'uuid', Rule::exists('academic_years', 'id')],
            'evaluation_period_id' => ['required', 'uuid', Rule::exists('evaluation_periods', 'id')],
            'course_id' => ['required', 'uuid', Rule::exists('courses', 'id')],

            'modality_id' => ['nullable', 'uuid', Rule::exists('modalities', 'id')],
            'shift_id' => ['nullable', 'uuid', Rule::exists('shifts', 'id')],

            'parallel_ids' => ['required', 'array', 'min:1'],
            'parallel_ids.*' => ['required', 'uuid', Rule::exists('parallels', 'id')],

            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['required', 'uuid', Rule::exists('subjects', 'id')],

            'qualitative_evaluation_template_id' => [
                'required',
                'uuid',
                Rule::exists('qualitative_evaluation_templates', 'id')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('is_active', true)),
            ],
        ];
    }

    public function messages(): array
    {
        return __('validation/Academic/qualitative-evaluation-component.custom');
    }

    public function attributes(): array
    {
        return __('validation/Academic/qualitative-evaluation-component.attributes');
    }
}
