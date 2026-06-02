<?php

namespace App\Http\Requests\Academic\QualitativeEvaluation\QualitativeEvaluationArea;

use App\Models\Administration\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQualitativeEvaluationAreaRequest extends FormRequest
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

        return $token && ! empty($token->tenant_id)
            ? (string) $token->tenant_id
            : null;
    }

    public function rules(): array
    {
        $area = $this->route('qualitativeEvaluationArea');

        if (is_object($area)) {
            $area = $area->id;
        }

        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            abort(400, __('messages.qualitative_evaluation_areas.tenant_not_resolved'));
        }

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('qualitative_evaluation_areas', 'code')
                    ->ignore($area)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return __('validation/academic/qualitative-evaluation-area.custom');
    }

    public function attributes(): array
    {
        return __('validation/academic/qualitative-evaluation-area.attributes');
    }
}
