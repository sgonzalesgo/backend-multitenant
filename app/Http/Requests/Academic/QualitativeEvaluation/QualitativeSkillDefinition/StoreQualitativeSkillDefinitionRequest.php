<?php

namespace App\Http\Requests\Academic\QualitativeEvaluation\QualitativeSkillDefinition;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQualitativeSkillDefinitionRequest extends FormRequest
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
            abort(400, __('messages.qualitative_skill_definitions.tenant_not_resolved'));
        }

        return [
            'qualitative_evaluation_area_id' => [
                'required',
                'uuid',
                Rule::exists('qualitative_evaluation_areas', 'id')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('qualitative_skill_definitions', 'code')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('qualitative_evaluation_area_id', $this->input('qualitative_evaluation_area_id'))),
            ],
            'name' => ['required', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/qualitative-skill-definition.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/qualitative-skill-definition.attributes');
    }
}
