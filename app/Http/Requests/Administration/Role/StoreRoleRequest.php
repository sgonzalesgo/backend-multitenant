<?php

namespace App\Http\Requests\Administration\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $guard = $this->input('guard_name', config('auth.defaults.guard', 'api'));

        return [
            'name'       => ['required','string','max:255'],
            'guard_name' => ['nullable','string','max:50'],
            // Ajusta a 'uuid' si tu tenants.id es uuid
            'tenant_id'  => ['required', Rule::exists('tenants','id')],
            // unicidad compuesta: (tenant_id, name, guard)
            'name'       => [
                'required','string','max:255',
                Rule::unique('roles','name')
                    ->where('guard_name', $guard)
                    ->where('tenant_id', $this->input('tenant_id')),
            ],
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
