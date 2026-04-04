<?php

namespace App\Http\Requests\Administration\Role;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        // Soporta que el id llegue en el body como "id" o vía {role_id}
        $roleId = $this->id ?? $this->route('role_id');
        $tenantId = $this->resolveCurrentTenantId();
        $guardName = $this->input('guard_name', config('auth.defaults.guard', 'api'));

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->ignore($roleId)
                    ->where(function ($query) use ($tenantId, $guardName) {
                        $query->where('guard_name', $guardName);

                        if ($tenantId) {
                            $query->where('tenant_id', $tenantId);
                        } else {
                            $query->whereNull('tenant_id');
                        }

                        return $query;
                    }),
            ],
            'guard_name' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return __('validation/administration/role.custom');
    }

    public function attributes(): array
    {
        return __('validation/administration/role.attributes');
    }
}
