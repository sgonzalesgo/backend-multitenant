<?php

namespace App\Http\Requests\Academic\QualitativeEvaluationSession;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenQualitativeEvaluationSessionRequest extends FormRequest
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
            'academic_year_id' => ['required', 'uuid', Rule::exists('academic_years', 'id')],
            'evaluation_period_id' => ['required', 'uuid', Rule::exists('evaluation_periods', 'id')],
            'course_id' => ['required', 'uuid', Rule::exists('courses', 'id')],
            'specialty_id' => ['nullable', 'uuid', Rule::exists('specialties', 'id')],
            'parallel_id' => ['required', 'uuid', Rule::exists('parallels', 'id')],
            'modality_id' => ['required', 'uuid', Rule::exists('modalities', 'id')],
            'shift_id' => ['required', 'uuid', Rule::exists('shifts', 'id')],
            'subject_id' => ['required', 'uuid', Rule::exists('subjects', 'id')],
            'instructor_id' => ['required', 'uuid', Rule::exists('instructors', 'id'),
            ],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return __('validation/Academic/qualitative-evaluation-session.custom');
    }

    public function attributes(): array
    {
        return __('validation/Academic/qualitative-evaluation-session.attributes');
    }
}
