<?php

namespace App\Http\Requests\Academic\Specialty;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpecialtyRequest extends FormRequest
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
            abort(400, __('messages.specialties.tenant_not_resolved'));
        }

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('specialties', 'code')
                    ->where(function ($query) use ($tenantId) {
                        return $query
                            ->where('tenant_id', $tenantId)
                            ->whereNull('deleted_at');
                    }),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('specialties', 'name')
                    ->where(function ($query) use ($tenantId) {
                        return $query
                            ->where('tenant_id', $tenantId)
                            ->whereNull('deleted_at');
                    }),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/specialty.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/specialty.attributes');
    }
}
