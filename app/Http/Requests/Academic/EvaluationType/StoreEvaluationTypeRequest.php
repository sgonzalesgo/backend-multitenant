<?php

namespace App\Http\Requests\Academic\EvaluationType;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationTypeRequest extends FormRequest
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
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.evaluation_types.tenant_not_resolved'));
        }

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('evaluation_types', 'code')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at')),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('evaluation_types', 'name')
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
