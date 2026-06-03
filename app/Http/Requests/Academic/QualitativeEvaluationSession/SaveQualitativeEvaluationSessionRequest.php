<?php

namespace App\Http\Requests\Academic\QualitativeEvaluationSession;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveQualitativeEvaluationSessionRequest extends FormRequest
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

        return $user->token()?->tenant_id
            ? (string) $user->token()->tenant_id
            : null;
    }

    public function rules(): array
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.qualitative_evaluation_sessions.tenant_not_resolved'));
        }

        return [
            'qualitative_evaluation_session_id' => [
                'required',
                'uuid',
                Rule::exists('qualitative_evaluation_sessions', 'id')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],

            'records' => ['required', 'array', 'min:1'],

            'records.*.student_id' => [
                'required',
                'uuid',
                Rule::exists('students', 'id'),
            ],

            'records.*.skills' => ['required', 'array', 'min:1'],

            'records.*.skills.*.qualitative_evaluation_component_id' => [
                'required',
                'uuid',
                Rule::exists('qualitative_evaluation_components', 'id')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],

            'records.*.skills.*.value' => [
                'nullable',
                'string',
                Rule::in(['I', 'EP', 'A', 'NE']),
            ],

            'records.*.skills.*.observation' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/qualitative-evaluation-session.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/qualitative-evaluation-session.attributes');
    }
}
