<?php

namespace App\Http\Requests\Academic\GradeComponentDefinition;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradeComponentDefinitionRequest extends FormRequest
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

        return $user->token()?->tenant_id
            ? (string) $user->token()->tenant_id
            : null;
    }

    public function rules(): array
    {
        $tenantId = $this->resolveCurrentTenantId();
        $definitionId = $this->route('gradeComponentDefinition')?->id;

        return [
            'component_key' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('grade_component_definitions', 'component_key')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->whereNull('deleted_at')
                    ->ignore($definitionId),
            ],

            'component_type' => ['sometimes', 'required', 'string', 'in:numeric,behavior,qualitative'],

            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('grade_component_definitions', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->whereNull('deleted_at')
                    ->ignore($definitionId),
            ],

            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        $attributes = trans('validation/Academic/grade_component_definition.attributes');

        return is_array($attributes) ? $attributes : [];
    }
}
