<?php

namespace App\Repositories\Academic\QualitativeEvaluation;

use App\Models\Academic\QualitativeEvaluationTemplate;
use App\Models\Academic\QualitativeEvaluationTemplateItem;
use App\Models\Academic\QualitativeSkillDefinition;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QualitativeEvaluationTemplateRepository
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
                'tenant' => __('messages.qualitative_evaluation_templates.tenant_not_resolved'),
            ]);
        }

        $rawQ = Arr::get($filters, 'q', '');
        $sort = Arr::get($filters, 'sort', 'created_at');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'name',
            'is_active',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'created_at';
        }

        $decodedQ = $this->decodeQuery($rawQ);

        $global = trim((string) Arr::get($decodedQ, 'global', ''));
        $columns = Arr::get($decodedQ, 'columns', []);

        $name = trim((string) Arr::get($columns, 'name', ''));
        $description = trim((string) Arr::get($columns, 'description', ''));
        $educationalLevelId = trim((string) Arr::get($columns, 'educational_level_id', ''));
        $courseId = trim((string) Arr::get($columns, 'course_id', ''));
        $evaluationPeriodId = trim((string) Arr::get($columns, 'evaluation_period_id', ''));
        $isActive = Arr::get($columns, 'is_active', '');

        return QualitativeEvaluationTemplate::query()
            ->with($this->relations())
            ->withCount('items')
            ->where('tenant_id', $tenantId)
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('name', 'ilike', "%{$global}%")
                        ->orWhere('description', 'ilike', "%{$global}%")
                        ->orWhereHas('course', fn ($courseQuery) => $courseQuery->where('name', 'ilike', "%{$global}%"))
                        ->orWhereHas('evaluationPeriod', fn ($periodQuery) => $periodQuery->where('name', 'ilike', "%{$global}%"))
                        ->orWhereHas('educationalLevel', fn ($levelQuery) => $levelQuery->where('name', 'ilike', "%{$global}%"));
                });
            })
            ->when($name !== '', fn ($query) => $query->where('name', 'ilike', "%{$name}%"))
            ->when($description !== '', fn ($query) => $query->where('description', 'ilike', "%{$description}%"))
            ->when($educationalLevelId !== '', fn ($query) => $query->where('educational_level_id', $educationalLevelId))
            ->when($courseId !== '', fn ($query) => $query->where('course_id', $courseId))
            ->when($evaluationPeriodId !== '', fn ($query) => $query->where('evaluation_period_id', $evaluationPeriodId))
            ->when($isActive !== '', function ($query) use ($isActive) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    /**
     * @throws ValidationException
     */
    public function active(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.qualitative_evaluation_templates.tenant_not_resolved'),
            ]);
        }

        $search = trim((string) Arr::get($filters, 'q', ''));
        $educationalLevelId = trim((string) Arr::get($filters, 'educational_level_id', ''));
        $courseId = trim((string) Arr::get($filters, 'course_id', ''));
        $evaluationPeriodId = trim((string) Arr::get($filters, 'evaluation_period_id', ''));
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        return QualitativeEvaluationTemplate::query()
            ->with($this->relations())
            ->withCount('items')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($educationalLevelId !== '', fn ($query) => $query->where('educational_level_id', $educationalLevelId))
            ->when($courseId !== '', fn ($query) => $query->where('course_id', $courseId))
            ->when($evaluationPeriodId !== '', fn ($query) => $query->where('evaluation_period_id', $evaluationPeriodId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(QualitativeEvaluationTemplate $qualitativeEvaluationTemplate): QualitativeEvaluationTemplate
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $qualitativeEvaluationTemplate->tenant_id !== $tenantId) {
            abort(404);
        }

        return $qualitativeEvaluationTemplate->load($this->relations());
    }

    /**
     * @throws ValidationException
     */
    public function create(array $data): QualitativeEvaluationTemplate
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.qualitative_evaluation_templates.tenant_not_resolved'),
            ]);
        }

        return DB::transaction(function () use ($data, $tenantId) {
            $template = QualitativeEvaluationTemplate::query()->create([
                'tenant_id' => $tenantId,
                ...$this->extractTemplatePayload($data),
            ]);

            $this->syncItems($template, Arr::get($data, 'skill_definition_ids', []), $tenantId);

            return $this->find($template->refresh());
        });
    }

    public function update(QualitativeEvaluationTemplate $qualitativeEvaluationTemplate, array $data): QualitativeEvaluationTemplate
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $qualitativeEvaluationTemplate->tenant_id !== $tenantId) {
            abort(404);
        }

        return DB::transaction(function () use ($qualitativeEvaluationTemplate, $data, $tenantId) {
            $qualitativeEvaluationTemplate->fill($this->extractTemplatePayload($data));
            $qualitativeEvaluationTemplate->save();

            if (array_key_exists('skill_definition_ids', $data)) {
                $this->syncItems(
                    $qualitativeEvaluationTemplate,
                    Arr::get($data, 'skill_definition_ids', []),
                    $tenantId
                );
            }

            return $this->find($qualitativeEvaluationTemplate->refresh());
        });
    }

    public function delete(QualitativeEvaluationTemplate $qualitativeEvaluationTemplate): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $qualitativeEvaluationTemplate->tenant_id !== $tenantId) {
            abort(404);
        }

        DB::transaction(function () use ($qualitativeEvaluationTemplate) {
            $qualitativeEvaluationTemplate->items()->delete();
            $qualitativeEvaluationTemplate->delete();
        });
    }

    /**
     * @throws ValidationException
     */
    protected function syncItems(QualitativeEvaluationTemplate $template, array $skillDefinitionIds, string $tenantId): void {

        $skillDefinitionIds = collect($skillDefinitionIds)
            ->filter()
            ->unique()
            ->values();

        $validSkillIds = QualitativeSkillDefinition::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('id', $skillDefinitionIds)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values();

        if ($validSkillIds->count() !== $skillDefinitionIds->count()) {
            throw ValidationException::withMessages([
                'skill_definition_ids' => __('messages.qualitative_evaluation_templates.invalid_skill_definitions'),
            ]);
        }

        $template->items()->delete();

        foreach ($validSkillIds as $index => $skillDefinitionId) {
            QualitativeEvaluationTemplateItem::query()->create([
                'tenant_id' => $tenantId,
                'qualitative_evaluation_template_id' => $template->id,
                'qualitative_skill_definition_id' => $skillDefinitionId,
                'default_order' => $index + 1,
                'is_required' => true,
            ]);
        }
    }

    protected function relations(): array
    {
        return [
            'educationalLevel:id,code,name',
            'course:id,code,name,educational_level_id',
            'evaluationPeriod:id,name',
            'items.skillDefinition.area:id,tenant_id,code,name,description,is_active',
            'items.skillDefinition:id,tenant_id,qualitative_evaluation_area_id,code,name,description,is_active',
        ];
    }

    protected function extractTemplatePayload(array $data): array
    {
        return Arr::only($data, [
            'name',
            'description',
            'educational_level_id',
            'course_id',
            'evaluation_period_id',
            'is_active',
        ]);
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
