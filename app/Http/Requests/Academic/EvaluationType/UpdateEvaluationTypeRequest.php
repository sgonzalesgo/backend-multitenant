<?php

namespace App\Http\Requests\Academic\EvaluationType;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEvaluationTypeRequest extends FormRequest
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
        $evaluationType = $this->route('evaluationType');

        if (is_object($evaluationType)) {
            $evaluationType = $evaluationType->id;
        }

        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.evaluation_types.tenant_not_resolved'));
        }

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('evaluation_types', 'code')
                    ->ignore($evaluationType)
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at')),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('evaluation_types', 'name')
                    ->ignore($evaluationType)
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at')),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/evaluation-type.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/evaluation-type.attributes');
    }
}
