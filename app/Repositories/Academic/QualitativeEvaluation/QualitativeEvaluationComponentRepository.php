<?php

namespace App\Repositories\Academic\QualitativeEvaluation;

use App\Models\Academic\QualitativeEvaluationComponent;
use App\Models\Academic\QualitativeEvaluationTemplate;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QualitativeEvaluationComponentRepository
{
    protected function resolveCurrentTenantId(): ?string
    {
        $currentTenant = Tenant::current();

        return $currentTenant ? (string) $currentTenant->id : null;
    }

    /**
     * @throws ValidationException
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.qualitative_evaluation_components.tenant_not_resolved'),
            ]);
        }

        $rawQ = Arr::get($filters, 'q', '');
        $sort = Arr::get($filters, 'sort', 'created_at');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'order',
            'is_required',
            'is_active',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'created_at';
        }

        $decodedQ = $this->decodeQuery($rawQ);

        $columns = Arr::get($decodedQ, 'columns', []);

        $academicYearId = trim((string) Arr::get($columns, 'academic_year_id', ''));
        $evaluationPeriodId = trim((string) Arr::get($columns, 'evaluation_period_id', ''));
        $courseId = trim((string) Arr::get($columns, 'course_id', ''));
        $parallelId = trim((string) Arr::get($columns, 'parallel_id', ''));
        $modalityId = trim((string) Arr::get($columns, 'modality_id', ''));
        $shiftId = trim((string) Arr::get($columns, 'shift_id', ''));
        $subjectId = trim((string) Arr::get($columns, 'subject_id', ''));
        $templateId = trim((string) Arr::get($columns, 'qualitative_evaluation_template_id', ''));
        $skillId = trim((string) Arr::get($columns, 'qualitative_skill_definition_id', ''));
        $isActive = Arr::get($columns, 'is_active', '');

        return QualitativeEvaluationComponent::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->when($academicYearId !== '', fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($evaluationPeriodId !== '', fn ($query) => $query->where('evaluation_period_id', $evaluationPeriodId))
            ->when($courseId !== '', fn ($query) => $query->where('course_id', $courseId))
            ->when($parallelId !== '', fn ($query) => $query->where('parallel_id', $parallelId))
            ->when($modalityId !== '', fn ($query) => $query->where('modality_id', $modalityId))
            ->when($shiftId !== '', fn ($query) => $query->where('shift_id', $shiftId))
            ->when($subjectId !== '', fn ($query) => $query->where('subject_id', $subjectId))
            ->when($templateId !== '', fn ($query) => $query->where('qualitative_evaluation_template_id', $templateId))
            ->when($skillId !== '', fn ($query) => $query->where('qualitative_skill_definition_id', $skillId))
            ->when($isActive !== '', function ($query) use ($isActive) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    public function find(QualitativeEvaluationComponent $qualitativeEvaluationComponent): QualitativeEvaluationComponent
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $qualitativeEvaluationComponent->tenant_id !== $tenantId) {
            abort(404);
        }

        return $qualitativeEvaluationComponent->load($this->relations());
    }

    /**
     * @throws ValidationException
     */
    public function generate(array $data): array
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.qualitative_evaluation_components.tenant_not_resolved'),
            ]);
        }

        $template = QualitativeEvaluationTemplate::query()
            ->where('tenant_id', $tenantId)
            ->where('id', Arr::get($data, 'qualitative_evaluation_template_id'))
            ->where('is_active', true)
            ->with(['items.skillDefinition'])
            ->first();

        if (! $template) {
            throw ValidationException::withMessages([
                'qualitative_evaluation_template_id' => __('messages.qualitative_evaluation_components.template_not_found'),
            ]);
        }

        $items = $template->items
            ->filter(fn ($item) => $item->skillDefinition && $item->skillDefinition->is_active);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'qualitative_evaluation_template_id' => __('messages.qualitative_evaluation_components.template_has_no_items'),
            ]);
        }

        $parallelIds = collect(Arr::get($data, 'parallel_ids', []))
            ->filter()
            ->unique()
            ->values();

        $subjectIds = collect(Arr::get($data, 'subject_ids', []))
            ->filter()
            ->unique()
            ->values();

        return DB::transaction(function () use ($data, $tenantId, $template, $items, $parallelIds, $subjectIds) {
            $created = 0;
            $existing = 0;

            foreach ($parallelIds as $parallelId) {
                foreach ($subjectIds as $subjectId) {
                    foreach ($items as $item) {
                        $attributes = [
                            'tenant_id' => $tenantId,
                            'academic_year_id' => Arr::get($data, 'academic_year_id'),
                            'evaluation_period_id' => Arr::get($data, 'evaluation_period_id'),
                            'course_id' => Arr::get($data, 'course_id'),
                            'parallel_id' => $parallelId,
                            'modality_id' => Arr::get($data, 'modality_id'),
                            'shift_id' => Arr::get($data, 'shift_id'),
                            'subject_id' => $subjectId,
                            'qualitative_skill_definition_id' => $item->qualitative_skill_definition_id,
                        ];

                        $component = QualitativeEvaluationComponent::query()
                            ->where($attributes)
                            ->first();

                        if ($component) {
                            $existing++;

                            continue;
                        }

                        QualitativeEvaluationComponent::query()->create([
                            ...$attributes,
                            'qualitative_evaluation_template_id' => $template->id,
                            'order' => $item->default_order,
                            'is_required' => $item->is_required,
                            'is_active' => true,
                        ]);

                        $created++;
                    }
                }
            }

            return [
                'created' => $created,
                'existing' => $existing,
                'total' => $created + $existing,
            ];
        });
    }

    public function delete(QualitativeEvaluationComponent $qualitativeEvaluationComponent): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $qualitativeEvaluationComponent->tenant_id !== $tenantId) {
            abort(404);
        }

        $qualitativeEvaluationComponent->delete();
    }

    protected function relations(): array
    {
        return [
            'academicYear:id,name',
            'evaluationPeriod:id,name',
            'course:id,code,name,educational_level_id',
            'parallel:id,code,name',
            'modality:id,code,name',
            'shift:id,code,name',
            'subject:id,code,name',
            'template:id,name',
            'skillDefinition.area:id,tenant_id,code,name',
            'skillDefinition:id,tenant_id,qualitative_evaluation_area_id,code,name,description,is_active',
        ];
    }

    protected function decodeQuery(mixed $rawQ): array
    {
        if (is_string($rawQ) && trim($rawQ) !== '') {
            $decoded = json_decode($rawQ, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($rawQ) ? $rawQ : [];
    }
}
