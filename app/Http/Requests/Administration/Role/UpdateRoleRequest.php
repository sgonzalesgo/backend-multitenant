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
        // Soporta que el id llegue en el body como "id" o vía {role_id}
        $roleId = $this->id ?? $this->route('role_id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->ignore($roleId)
                    ->where('guard_name', $this->input('guard_name', config('auth.defaults.guard', 'api'))),
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
