<?php

namespace App\Http\Requests\Administration\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Role|string|null $routeRole */
        $routeRole = $this->route('role');
        $roleId    = is_object($routeRole) ? $routeRole->id : $routeRole;

        $guard     = $this->input('guard_name', config('auth.defaults.guard', 'api'));
        $tenantId  = $this->input('tenant_id') ?? (is_object($routeRole) ? $routeRole->tenant_id : null);

        return [
            'name'       => [
                'sometimes','required','string','max:255',
                Rule::unique('roles','name')
                    ->ignore($roleId)
                    ->where('guard_name', $guard)
                    ->where('tenant_id', $tenantId),
            ],
            'guard_name' => ['sometimes','nullable','string','max:50'],
            'tenant_id'  => ['sometimes', Rule::exists('tenants','id')], // normalmente no se cambia
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
