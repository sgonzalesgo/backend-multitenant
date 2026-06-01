<?php

namespace App\Http\Requests\Academic\GradeComponentDefinition;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeComponentDefinitionRequest extends FormRequest
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

        return [
            'component_key' => [
                'required',
                'string',
                'max:100',
                Rule::unique('grade_component_definitions', 'component_key')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->whereNull('deleted_at'),
            ],

            'component_type' => ['required', 'string', 'in:numeric,behavior,qualitative'],

            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('grade_component_definitions', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->whereNull('deleted_at'),
            ],

            'name' => ['required', 'string', 'max:255'],
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
