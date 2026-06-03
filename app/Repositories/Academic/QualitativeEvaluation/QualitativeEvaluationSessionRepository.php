<?php

namespace App\Repositories\Academic\QualitativeEvaluation;

use App\Models\Academic\Enrollment;
use App\Models\Academic\QualitativeEvaluationComponent;
use App\Models\Academic\QualitativeEvaluationRecord;
use App\Models\Academic\QualitativeEvaluationRecordSkill;
use App\Models\Academic\QualitativeEvaluationSession;
use App\Models\Administration\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QualitativeEvaluationSessionRepository
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

    protected function requireTenantId(): string
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.qualitative_evaluation_sessions.tenant_not_resolved'),
            ]);
        }

        return $tenantId;
    }

    public function open(array $data): array
    {
        $tenantId = $this->requireTenantId();

        $components = $this->getComponents($tenantId, $data);

        if ($components->isEmpty()) {
            throw ValidationException::withMessages([
                'components' => __('messages.qualitative_evaluation_sessions.components_not_generated'),
            ]);
        }

        $enrollments = $this->getEnrollments($tenantId, $data);

        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'students' => __('messages.qualitative_evaluation_sessions.students_not_found'),
            ]);
        }

        return DB::transaction(function () use ($data, $tenantId, $components, $enrollments) {
            $session = QualitativeEvaluationSession::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'academic_year_id' => Arr::get($data, 'academic_year_id'),
                    'evaluation_period_id' => Arr::get($data, 'evaluation_period_id'),
                    'course_id' => Arr::get($data, 'course_id'),
                    'specialty_id' => Arr::get($data, 'specialty_id'),
                    'parallel_id' => Arr::get($data, 'parallel_id'),
                    'modality_id' => Arr::get($data, 'modality_id'),
                    'shift_id' => Arr::get($data, 'shift_id'),
                    'subject_id' => Arr::get($data, 'subject_id'),
                ],
                [
                    'name' => Arr::get($data, 'name', 'Evaluación cualitativa'),
                    'is_closed' => false,
                ]
            );

            foreach ($enrollments as $enrollment) {
                $record = QualitativeEvaluationRecord::query()->firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'qualitative_evaluation_session_id' => $session->id,
                        'student_id' => $enrollment->student_id,
                    ],
                    [
                        'enrollment_id' => $enrollment->id,
                    ]
                );

                foreach ($components as $component) {
                    QualitativeEvaluationRecordSkill::query()->firstOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'qualitative_evaluation_record_id' => $record->id,
                            'qualitative_evaluation_component_id' => $component->id,
                        ],
                        [
                            'value' => null,
                            'observation' => null,
                        ]
                    );
                }
            }

            return $this->matrix($session->refresh());
        });
    }

    public function find(QualitativeEvaluationSession $session): array
    {
        return $this->matrix($session);
    }

    public function save(array $data): array
    {
        $tenantId = $this->requireTenantId();

        $session = QualitativeEvaluationSession::query()
            ->where('tenant_id', $tenantId)
            ->where('id', Arr::get($data, 'qualitative_evaluation_session_id'))
            ->first();

        if (! $session) {
            throw ValidationException::withMessages([
                'qualitative_evaluation_session_id' => __('messages.qualitative_evaluation_sessions.not_found'),
            ]);
        }

        if ($session->is_closed) {
            throw ValidationException::withMessages([
                'qualitative_evaluation_session_id' => __('messages.qualitative_evaluation_sessions.already_closed'),
            ]);
        }

        return DB::transaction(function () use ($data, $tenantId, $session) {
            foreach (Arr::get($data, 'records', []) as $recordPayload) {
                $record = QualitativeEvaluationRecord::query()
                    ->where('tenant_id', $tenantId)
                    ->where('qualitative_evaluation_session_id', $session->id)
                    ->where('student_id', Arr::get($recordPayload, 'student_id'))
                    ->first();

                if (! $record) {
                    continue;
                }

                foreach (Arr::get($recordPayload, 'skills', []) as $skillPayload) {
                    QualitativeEvaluationRecordSkill::query()
                        ->where('tenant_id', $tenantId)
                        ->where('qualitative_evaluation_record_id', $record->id)
                        ->where(
                            'qualitative_evaluation_component_id',
                            Arr::get($skillPayload, 'qualitative_evaluation_component_id')
                        )
                        ->update([
                            'value' => Arr::get($skillPayload, 'value'),
                            'observation' => Arr::get($skillPayload, 'observation'),
                        ]);
                }
            }

            return $this->matrix($session->refresh());
        });
    }

    public function close(QualitativeEvaluationSession $session): array
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        $session->is_closed = true;
        $session->save();

        return $this->matrix($session->refresh());
    }

    public function reopen(QualitativeEvaluationSession $session): array
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        $session->is_closed = false;
        $session->save();

        return $this->matrix($session->refresh());
    }

    public function matrix(QualitativeEvaluationSession $session): array
    {
        $tenantId = $this->requireTenantId();

        if ((string) $session->tenant_id !== $tenantId) {
            abort(404);
        }

        $session->load($this->relations());

        $recordSkills = $session->records
            ->flatMap(fn ($record) => $record->skills)
            ->filter(fn ($recordSkill) => $recordSkill->component)
            ->sortBy(fn ($recordSkill) => $recordSkill->component->order);

        $components = $recordSkills
            ->pluck('component')
            ->unique('id')
            ->sortBy('order')
            ->values();

        $columns = $components->map(function ($component, int $index) {
            $skill = $component->skillDefinition;
            $area = $skill?->area;

            return [
                'field' => 'component_'.$component->id,
                'component_id' => $component->id,
                'order' => $component->order,
                'label' => $skill?->code ?: 'D'.($index + 1),
                'header' => $skill?->code ?: 'Destreza '.($index + 1),
                'area_id' => $area?->id,
                'area_code' => $area?->code,
                'area_name' => $area?->name,
                'skill_id' => $skill?->id,
                'skill_code' => $skill?->code,
                'skill_name' => $skill?->name,
                'skill_description' => $skill?->description,
                'is_required' => (bool) $component->is_required,
            ];
        })->values();

        $rows = $session->records
            ->sortBy(fn ($record) => strtoupper($record->student?->person?->full_name ?? ''))
            ->map(function ($record) use ($components) {
                $skillsByComponent = $record->skills->keyBy('qualitative_evaluation_component_id');

                $skills = [];

                foreach ($components as $component) {
                    $recordSkill = $skillsByComponent->get($component->id);

                    $skills[$component->id] = [
                        'record_skill_id' => $recordSkill?->id,
                        'qualitative_evaluation_component_id' => $component->id,
                        'value' => $recordSkill?->value,
                        'observation' => $recordSkill?->observation,
                    ];
                }

                return [
                    'id' => $record->id,
                    'record_id' => $record->id,
                    'student_id' => $record->student_id,
                    'enrollment_id' => $record->enrollment_id,
                    'student_code' => $record->student?->student_code,
                    'student_name' => $record->student?->person?->full_name,
                    'student_legal_id' => $record->student?->person?->legal_id,
                    'skills' => $skills,
                ];
            })
            ->values();

        return [
            'session' => [
                'id' => $session->id,
                'name' => $session->name,
                'is_closed' => (bool) $session->is_closed,
                'academic_year' => $session->academicYear,
                'evaluation_period' => $session->evaluationPeriod,
                'course' => $session->course,
                'specialty' => $session->specialty,
                'parallel' => $session->parallel,
                'modality' => $session->modality,
                'shift' => $session->shift,
                'subject' => $session->subject,
            ],
            'scale' => [
                ['value' => 'I', 'label' => 'Iniciado'],
                ['value' => 'EP', 'label' => 'En Proceso'],
                ['value' => 'A', 'label' => 'Adquirido'],
                ['value' => 'NE', 'label' => 'No Evaluado'],
            ],
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    protected function getComponents(string $tenantId, array $data): Collection
    {
        return QualitativeEvaluationComponent::query()
            ->with('skillDefinition.area')
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', Arr::get($data, 'academic_year_id'))
            ->where('evaluation_period_id', Arr::get($data, 'evaluation_period_id'))
            ->where('course_id', Arr::get($data, 'course_id'))
            ->where('parallel_id', Arr::get($data, 'parallel_id'))
            ->where('modality_id', Arr::get($data, 'modality_id'))
            ->where('shift_id', Arr::get($data, 'shift_id'))
            ->where('subject_id', Arr::get($data, 'subject_id'))
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    protected function getEnrollments(string $tenantId, array $data): Collection
    {
        return Enrollment::query()
            ->select('enrollments.*')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->join('persons', 'persons.id', '=', 'students.person_id')
            ->with([
                'student:id,person_id,student_code,status',
                'student.person:id,full_name,legal_id,email,photo',
                'enrollmentStatus:id,code,name',
            ])
            ->where('enrollments.tenant_id', $tenantId)
            ->where('enrollments.academic_year_id', Arr::get($data, 'academic_year_id'))
            ->where('enrollments.course_id', Arr::get($data, 'course_id'))
            ->where('enrollments.parallel_id', Arr::get($data, 'parallel_id'))
            ->where('enrollments.modality_id', Arr::get($data, 'modality_id'))
            ->where('enrollments.shift_id', Arr::get($data, 'shift_id'))
            ->when(
                Arr::get($data, 'specialty_id'),
                fn ($query, $value) => $query->where('enrollments.specialty_id', $value),
                fn ($query) => $query->whereNull('enrollments.specialty_id')
            )
            ->whereHas('enrollmentStatus', fn ($query) => $query->where('code', 'active'))
            ->orderByRaw('UPPER(persons.full_name) ASC')
            ->get();
    }

    protected function relations(): array
    {
        return [
            'academicYear:id,name',
            'evaluationPeriod:id,name',
            'course:id,code,name',
            'specialty:id,code,name',
            'parallel:id,code,name',
            'modality:id,code,name',
            'shift:id,code,name',
            'subject:id,code,name',
            'records.student.person:id,full_name,legal_id,email,photo',
            'records.enrollment:id,student_id,enrollment_status_id',
            'records.skills.component.skillDefinition.area:id,tenant_id,code,name',
            'records.skills.component.skillDefinition:id,tenant_id,qualitative_evaluation_area_id,code,name,description,is_active',
        ];
    }
}
