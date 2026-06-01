<?php

namespace App\Repositories\Academic;

use App\Models\Academic\GradeComponent;
use App\Models\Academic\GradeComponentTemplate;
use App\Models\Administration\Tenant;
use App\Models\Academic\Subject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradeComponentTemplateRepository
{
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

    /**
     * @throws ValidationException
     */
    protected function requireTenantId(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.grade_component_template.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    public function index(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->requireTenantId();

        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        return GradeComponentTemplate::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->when(Arr::get($filters, 'academic_year_id'), fn ($q, $v) => $q->where('academic_year_id', $v))
            ->when(Arr::get($filters, 'evaluation_period_id'), fn ($q, $v) => $q->where('evaluation_period_id', $v))
            ->when(Arr::get($filters, 'educational_level_id'), fn ($q, $v) => $q->where('educational_level_id', $v))
            ->when(Arr::get($filters, 'course_id'), fn ($q, $v) => $q->where('course_id', $v))
            ->when(Arr::get($filters, 'specialty_id'), fn ($q, $v) => $q->where('specialty_id', $v))
            ->when(Arr::get($filters, 'modality_id'), fn ($q, $v) => $q->where('modality_id', $v))
            ->when(Arr::get($filters, 'shift_id'), fn ($q, $v) => $q->where('shift_id', $v))
            ->when(Arr::get($filters, 'grading_mode'), fn ($q, $v) => $q->where('grading_mode', $v))
            ->when(Arr::has($filters, 'is_active'), fn ($q) => $q->where('is_active', filter_var(Arr::get($filters, 'is_active'), FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function store(array $data): GradeComponentTemplate
    {
        $tenantId = $this->requireTenantId();

        return DB::transaction(function () use ($tenantId, $data) {
            $template = GradeComponentTemplate::query()->create([
                'tenant_id' => $tenantId,
                'academic_year_id' => Arr::get($data, 'academic_year_id'),
                'evaluation_period_id' => Arr::get($data, 'evaluation_period_id'),
                'educational_level_id' => Arr::get($data, 'educational_level_id'),
                'course_id' => Arr::get($data, 'course_id'),
                'specialty_id' => Arr::get($data, 'specialty_id'),
                'modality_id' => Arr::get($data, 'modality_id'),
                'shift_id' => Arr::get($data, 'shift_id'),
                'grading_mode' => Arr::get($data, 'grading_mode'),
                'code' => Arr::get($data, 'code'),
                'name' => Arr::get($data, 'name'),
                'description' => Arr::get($data, 'description'),
                'is_active' => (bool) Arr::get($data, 'is_active', true),
            ]);

            $this->syncItems($template, Arr::get($data, 'items', []));

            return $template->refresh()->load($this->relations());
        });
    }

    public function update(GradeComponentTemplate $template, array $data): GradeComponentTemplate
    {
        $tenantId = $this->requireTenantId();

        if ((string) $template->tenant_id !== $tenantId) {
            abort(404);
        }

        return DB::transaction(function () use ($template, $data) {
            $template->update([
                'academic_year_id' => Arr::get($data, 'academic_year_id', $template->academic_year_id),
                'evaluation_period_id' => Arr::get($data, 'evaluation_period_id', $template->evaluation_period_id),
                'educational_level_id' => Arr::get($data, 'educational_level_id', $template->educational_level_id),
                'course_id' => Arr::get($data, 'course_id', $template->course_id),
                'specialty_id' => Arr::get($data, 'specialty_id', $template->specialty_id),
                'modality_id' => Arr::get($data, 'modality_id', $template->modality_id),
                'shift_id' => Arr::get($data, 'shift_id', $template->shift_id),
                'grading_mode' => Arr::get($data, 'grading_mode', $template->grading_mode),
                'code' => Arr::get($data, 'code', $template->code),
                'name' => Arr::get($data, 'name', $template->name),
                'description' => Arr::get($data, 'description', $template->description),
                'is_active' => Arr::has($data, 'is_active')
                    ? (bool) Arr::get($data, 'is_active')
                    : $template->is_active,
            ]);

            if (Arr::has($data, 'items')) {
                $this->syncItems($template, Arr::get($data, 'items', []));
            }

            return $template->refresh()->load($this->relations());
        });
    }

    public function delete(GradeComponentTemplate $template): void
    {
        $tenantId = $this->requireTenantId();

        if ((string) $template->tenant_id !== $tenantId) {
            abort(404);
        }

        $template->delete();
    }

    public function generateComponents(GradeComponentTemplate $template, array $data): array
    {
        $tenantId = $this->requireTenantId();

        if ((string) $template->tenant_id !== $tenantId) {
            abort(404);
        }

        if (! $template->is_active) {
            throw ValidationException::withMessages([
                'grade_component_template_id' => __('messages.grade_component_template.template_inactive'),
            ]);
        }

        $items = $template->items()
            ->with('definition')
            ->where('is_active', true)
            ->orderBy('default_order')
            ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => __('messages.grade_component_template.template_has_no_items'),
            ]);
        }

        $parallelIds = Arr::get($data, 'parallel_ids', []);
        $subjectIds = Arr::get($data, 'subject_ids', []);

        $subjects = Subject::query()
            ->whereIn('id', $subjectIds)
            ->get()
            ->keyBy(fn (Subject $subject) => (string) $subject->id);

        return DB::transaction(function () use (
            $tenantId,
            $template,
            $items,
            $data,
            $parallelIds,
            $subjectIds,
            $subjects
        ) {
            $components = [];

            foreach ($parallelIds as $parallelId) {
                foreach ($subjectIds as $subjectId) {
                    $subject = $subjects->get((string) $subjectId);

                    foreach ($items as $item) {
                        $definition = $item->definition;

                        if (! $definition || ! $definition->is_active) {
                            continue;
                        }

                        $component = GradeComponent::query()->firstOrCreate(
                            [
                                'tenant_id' => $tenantId,
                                'academic_year_id' => Arr::get($data, 'academic_year_id', $template->academic_year_id),
                                'evaluation_period_id' => Arr::get($data, 'evaluation_period_id', $template->evaluation_period_id),
                                'course_id' => Arr::get($data, 'course_id'),
                                'specialty_id' => Arr::get($data, 'specialty_id'),
                                'parallel_id' => $parallelId,
                                'modality_id' => Arr::get($data, 'modality_id'),
                                'shift_id' => Arr::get($data, 'shift_id'),
                                'subject_id' => $subjectId,
                                'component_key' => $definition->component_key,
                            ],
                            [
                                'evaluation_type_id' => $subject?->evaluation_type_id,
                                'component_type' => $definition->component_type,
                                'code' => $definition->code,
                                'name' => $definition->name,
                                'description' => $definition->description,

                                'weight' => $item->weight,
                                'max_score' => $item->max_score,
                                'default_order' => $item->default_order,
                                'is_required' => $item->is_required,
                                'is_system_calculated' => $item->is_system_calculated,
                                'is_active' => true,
                                'settings' => $item->settings,
                            ]
                        );

                        $components[] = $component->refresh();
                    }
                }
            }

            return $components;
        });
    }

    protected function syncItems(GradeComponentTemplate $template, array $items): void
    {
        $tenantId = (string) $template->tenant_id;

        $template->items()->delete();

        foreach ($items as $index => $item) {
            $template->items()->create([
                'tenant_id' => $tenantId,
                'grade_component_definition_id' => Arr::get($item, 'grade_component_definition_id'),
                'weight' => Arr::get($item, 'weight', 0),
                'max_score' => Arr::get($item, 'max_score', 10),
                'default_order' => Arr::get($item, 'default_order', $index + 1),
                'is_required' => (bool) Arr::get($item, 'is_required', true),
                'is_system_calculated' => (bool) Arr::get($item, 'is_system_calculated', false),
                'is_active' => (bool) Arr::get($item, 'is_active', true),
                'settings' => Arr::get($item, 'settings'),
            ]);
        }
    }

    protected function relations(): array
    {
        return [
            'academicYear:id,name,code',
            'evaluationPeriod:id,name,code,start_date,end_date',
            'educationalLevel:id,name,code',
            'course:id,name,code',
            'specialty:id,name,code',
            'modality:id,name,code',
            'shift:id,name,code',
            'items' => fn ($query) => $query->orderBy('default_order'),
            'items.definition:id,component_key,component_type,code,name,description,is_active',
        ];
    }
}
