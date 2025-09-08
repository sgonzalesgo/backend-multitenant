<?php

namespace App\Http\Requests\Administration\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncUserRolesInTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'   => ['required','uuid', Rule::exists('users','id')],
            'tenant_id' => ['required', Rule::exists('tenants','id')],
            'roles'     => ['required','array'],
            'roles.*'   => [Rule::exists('roles','id')],
        ];
    }

    public function messages(): array
    {
        return __('validation/administration/assign_roles.custom');
    }

    public function attributes(): array
    {
        return __('validation/administration/assign_roles.attributes');
    }
}
