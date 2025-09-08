<?php

namespace App\Http\Requests\Administration\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Igual que en store, podés chequear con Gate/Policy
        return true;
    }

    public function rules(): array
    {
        // Soporta que el id llegue en el body como "id" o vía {permission_id}
        $permissionId = $this->id ?? $this->route('permission_id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')
                    ->ignore($permissionId)
                    ->where('guard_name', $this->input('guard_name', config('auth.defaults.guard', 'api'))),
            ],
            'guard_name' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return __('validation/administration/permission.custom');
    }

    public function attributes(): array
    {
        return __('validation/administration/permission.attributes');
    }
}
