<?php

namespace App\Http\Requests\Academic\EducationalLevel;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEducationalLevelRequest extends FormRequest
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

        if (! $token || empty($token->tenant_id)) {
            return null;
        }

        return (string) $token->tenant_id;
    }

    public function rules(): array
    {
        $educationalLevel = $this->route('educationalLevel');

        if (is_object($educationalLevel)) {
            $educationalLevel = $educationalLevel->id;
        }

        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.educational_levels.tenant_not_resolved'));
        }

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('educational_levels', 'code')
                    ->ignore($educationalLevel)
                    ->where(fn ($q) => $q
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at')),
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('educational_levels', 'name')
                    ->ignore($educationalLevel)
                    ->where(fn ($q) => $q
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at')),
            ],

            'sort_order' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('educational_levels', 'sort_order')
                    ->ignore($educationalLevel)
                    ->where(fn ($q) => $q
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at')),
            ],

            'start_number' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
            ],

            'end_number' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'gte:start_number',
            ],

            'has_specialty' => [
                'nullable',
                'boolean',
            ],

            'next_educational_level_id' => [
                'nullable',
                'uuid',
                Rule::exists('educational_levels', 'id')
                    ->where(fn ($q) => $q
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at')),
                'different:id',
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/educational_level.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/educational_level.attributes');
    }
}
