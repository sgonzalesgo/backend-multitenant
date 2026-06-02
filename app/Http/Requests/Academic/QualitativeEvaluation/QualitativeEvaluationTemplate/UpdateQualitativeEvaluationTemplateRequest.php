<?php

namespace App\Http\Requests\Academic\QualitativeEvaluation\QualitativeEvaluationTemplate;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQualitativeEvaluationTemplateRequest extends FormRequest
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
        $template = $this->route('qualitativeEvaluationTemplate');

        if (is_object($template)) {
            $template = $template->id;
        }

        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.qualitative_evaluation_templates.tenant_not_resolved'));
        }

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('qualitative_evaluation_templates', 'name')
                    ->ignore($template)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'description' => ['nullable', 'string', 'max:255'],

            'educational_level_id' => [
                'nullable',
                'uuid',
                Rule::exists('educational_levels', 'id'),
            ],
            'course_id' => [
                'nullable',
                'uuid',
                Rule::exists('courses', 'id'),
            ],
            'evaluation_period_id' => [
                'nullable',
                'uuid',
                Rule::exists('evaluation_periods', 'id'),
            ],

            'is_active' => ['nullable', 'boolean'],

            'skill_definition_ids' => [
                'sometimes',
                'required',
                'array',
                'min:1',
            ],
            'skill_definition_ids.*' => [
                'required',
                'uuid',
                Rule::exists('qualitative_skill_definitions', 'id')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('is_active', true)),
            ],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/qualitative-evaluation-template.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/qualitative-evaluation-template.attributes');
    }
}
