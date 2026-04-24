<?php

namespace App\Http\Requests\Academic\Classroom;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassroomRequest extends FormRequest
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
        $classroom = $this->route('classroom');

        if (is_object($classroom)) {
            $classroom = $classroom->id;
        }

        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.classrooms.tenant_not_resolved'));
        }

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('classrooms', 'code')
                    ->ignore($classroom)
                    ->where(fn ($q) => $q
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at')),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('classrooms', 'name')
                    ->ignore($classroom)
                    ->where(fn ($q) => $q
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at')),
            ],
            'capacity' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'location' => [
                'nullable',
                'string',
                'max:150',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/classroom.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/classroom.attributes');
    }
}
