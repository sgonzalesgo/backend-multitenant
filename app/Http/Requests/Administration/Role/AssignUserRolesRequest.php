<?php

namespace App\Http\Requests\Administration\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // o Gate: can('Assign roles')
    }

    public function rules(): array
    {
        return [
            'user_id'    => ['required', 'uuid', Rule::exists('users', 'id')],
            'tenant_id'  => ['required', Rule::exists('tenants', 'id')], // ajusta tipo (uuid/integer) a tu schema
            'roles'      => ['required','array'],
            'roles.*'    => [Rule::exists('roles', 'id')],
            'detach_missing' => ['sometimes','boolean'], // true: reemplaza; false: agrega sin quitar
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
