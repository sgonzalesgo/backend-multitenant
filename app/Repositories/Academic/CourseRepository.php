<?php

namespace App\Repositories\Academic;

use App\Models\Academic\Course;
use App\Models\Academic\EducationalLevel;
use App\Models\Academic\Instructor;
use App\Models\Administration\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseRepository
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
                'tenant' => __('messages.courses.tenant_not_resolved'),
            ]);
        }

        $rawQ = Arr::get($filters, 'q', '');
        $sort = Arr::get($filters, 'sort', 'created_at');
        $dir = strtolower((string) Arr::get($filters, 'dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) Arr::get($filters, 'per_page', 15), 100));

        if (! in_array($sort, [
            'code',
            'name',
            'capacity',
            'level_number',
            'credits',
            'status',
            'created_at',
            'updated_at',
        ], true)) {
            $sort = 'created_at';
        }

        $decodedQ = [];

        if (is_string($rawQ) && trim($rawQ) !== '') {
            $decoded = json_decode($rawQ, true);
            $decodedQ = is_array($decoded) ? $decoded : [];
        } elseif (is_array($rawQ)) {
            $decodedQ = $rawQ;
        }

        $global = trim((string) Arr::get($decodedQ, 'global', ''));
        $columns = Arr::get($decodedQ, 'columns', []);

        $code = trim((string) Arr::get($columns, 'code', ''));
        $name = trim((string) Arr::get($columns, 'name', ''));
        $educationalLevelId = trim((string) Arr::get($columns, 'educational_level_id', ''));
        $instructorId = trim((string) Arr::get($columns, 'instructor_id', ''));
        $status = trim((string) Arr::get($columns, 'status', ''));
        $levelNumber = trim((string) Arr::get($columns, 'level_number', ''));

        return Course::query()
            ->with($this->relations())
            ->where('tenant_id', $tenantId)
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('code', 'ilike', "%{$global}%")
                        ->orWhere('name', 'ilike', "%{$global}%")
                        ->orWhere('description', 'ilike', "%{$global}%")
                        ->orWhere('status', 'ilike', "%{$global}%")
                        ->orWhereHas('educationalLevel', function ($levelQuery) use ($global) {
                            $levelQuery->where('name', 'ilike', "%{$global}%");
                        })
                        ->orWhereHas('instructor.person', function ($personQuery) use ($global) {
                            $personQuery->where('full_name', 'ilike', "%{$global}%")
                                ->orWhere('email', 'ilike', "%{$global}%")
                                ->orWhere('legal_id', 'ilike', "%{$global}%");
                        });
                });
            })
            ->when($code !== '', fn ($query) => $query->where('code', 'ilike', "%{$code}%"))
            ->when($name !== '', fn ($query) => $query->where('name', 'ilike', "%{$name}%"))
            ->when($educationalLevelId !== '', fn ($query) => $query->where('educational_level_id', $educationalLevelId))
            ->when($instructorId !== '', fn ($query) => $query->where('instructor_id', $instructorId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($levelNumber !== '', fn ($query) => $query->where('level_number', $levelNumber))
            ->orderBy($sort, $dir)
            ->paginate($perPage);
    }

    /**
     * @throws ValidationException
     */
    /**
     * @throws ValidationException
     */
    public function active(array $filters = []): LengthAwarePaginator
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.courses.tenant_not_resolved'),
            ]);
        }

        $rawQ = Arr::get($filters, 'q', []);
        $decodedQ = [];

        if (is_string($rawQ) && trim($rawQ) !== '') {
            $decoded = json_decode($rawQ, true);
            $decodedQ = is_array($decoded) ? $decoded : [];
        } elseif (is_array($rawQ)) {
            $decodedQ = $rawQ;
        }

        $global = trim((string) Arr::get($decodedQ, 'global', ''));
        $columns = Arr::get($decodedQ, 'columns', []);

        return Course::query()
            ->with([
                'educationalLevel:id,tenant_id,code,name,has_specialty',
                'educationalLevel.specialties:id,tenant_id,code,name,description,is_active',
                'instructor:id,person_id',
                'instructor.person:id,full_name,email,photo',
            ])
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')

            // 🔍 GLOBAL SEARCH
            ->when($global !== '', function ($query) use ($global) {
                $query->where(function ($q) use ($global) {
                    $q->where('code', 'ilike', "%{$global}%")
                        ->orWhere('name', 'ilike', "%{$global}%")
                        ->orWhereHas('educationalLevel', function ($q2) use ($global) {
                            $q2->where('name', 'ilike', "%{$global}%");
                        })
                        ->orWhereHas('instructor.person', function ($q3) use ($global) {
                            $q3->where('full_name', 'ilike', "%{$global}%")
                                ->orWhere('email', 'ilike', "%{$global}%");
                        });
                });
            })

            // 🔍 COLUMN FILTERS
            ->when(! empty($columns['code']), fn ($q) =>
            $q->where('code', 'ilike', "%{$columns['code']}%")
            )

            ->when(! empty($columns['level_number']), fn ($q) =>
            $q->where('level_number', $columns['level_number'])
            )

            ->when(! empty($columns['name']), fn ($q) =>
            $q->where('name', 'ilike', "%{$columns['name']}%")
            )

            ->when(! empty($columns['educational_level_id']), fn ($q) =>
            $q->where('educational_level_id', $columns['educational_level_id'])
            )

            ->when(! empty($columns['instructor_id']), fn ($q) =>
            $q->where('instructor_id', $columns['instructor_id'])
            )

            ->when(! empty($columns['status']), fn ($q) =>
            $q->where('status', $columns['status'])
            )

            ->orderBy('created_at', 'desc')
            ->paginate(
                max(1, min((int) Arr::get($filters, 'per_page', 15), 100))
            );
    }

    public function find(Course $course): Course
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $course->tenant_id !== $tenantId) {
            abort(404);
        }

        return $course->load($this->relations());
    }

    /**
     * @throws ValidationException
     */
    /**
     * @throws ValidationException
     */
    public function create(array $data): Course
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId) {
            throw ValidationException::withMessages([
                'tenant' => __('messages.courses.tenant_not_resolved'),
            ]);
        }

        return DB::transaction(function () use ($data, $tenantId) {
            $this->validateRelatedModels($data, $tenantId);

            $code = trim((string) Arr::get($data, 'code', ''));

            if ($code === '') {
                $code = $this->generateCourseCode($tenantId);
            }

            $this->validateUniqueCode($code, $tenantId);

            $payload = $this->extractCoursePayload($data);
            $payload['code'] = $code;

            $course = Course::query()->create([
                'tenant_id' => $tenantId,
                ...$payload,
            ]);

            return $this->find($course->refresh());
        });
    }

    public function update(Course $course, array $data): Course
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $course->tenant_id !== $tenantId) {
            abort(404);
        }

        return DB::transaction(function () use ($course, $data, $tenantId) {
            $this->validateRelatedModels($data, $tenantId);

            if (array_key_exists('code', $data)) {
                $this->validateUniqueCode($data['code'], $tenantId, $course->id);
            }

            $course->fill($this->extractCoursePayload($data));
            $course->save();

            return $this->find($course->refresh());
        });
    }

    public function delete(Course $course): void
    {
        $tenantId = $this->resolveCurrentTenantId();

        if (! $tenantId || (string) $course->tenant_id !== $tenantId) {
            abort(404);
        }

        DB::transaction(function () use ($course) {
            $course->delete();
        });
    }

    protected function relations(): array
    {
        return [
            'tenant:id,name',
            'educationalLevel:id,tenant_id,code,name,has_specialty',
            'educationalLevel.specialties:id,tenant_id,code,name,description,is_active',
            'instructor:id,person_id,status',
            'instructor.person:id,full_name,email,phone,legal_id,legal_id_type,photo',
        ];
    }

    protected function extractCoursePayload(array $data): array
    {
        return Arr::only($data, [
            'educational_level_id',
            'instructor_id',
            'level_number',
            'code',
            'name',
            'description',
            'capacity',
            'credits',
            'theoretical_hours',
            'practical_hours',
            'total_hours',
            'status',
            'notes',
        ]);
    }

    /**
     * @throws ValidationException
     */
    protected function validateUniqueCode(string $code, string $tenantId, ?string $ignoreId = null): void
    {
        $exists = Course::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => __('messages.courses.code_taken'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function validateRelatedModels(array $data, string $tenantId): void
    {
        if (array_key_exists('educational_level_id', $data)) {
            $exists = EducationalLevel::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['educational_level_id'])
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'educational_level_id' => __('messages.courses.invalid_educational_level'),
                ]);
            }
        }

        if (array_key_exists('instructor_id', $data)) {
            $exists = Instructor::query()
                ->where('id', $data['instructor_id'])
                ->where('status', 'active')
                ->whereHas('person', function ($query) {
                    $query->whereNull('deleted_at')
                        ->whereNull('deceased_at');
                })
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'instructor_id' => __('messages.courses.invalid_instructor'),
                ]);
            }
        }
    }

    protected function generateCourseCode(string $tenantId): string
    {
        do {
            $code = 'CRS-' . now()->format('ymd') . '-' . strtoupper(str()->random(6));

            $exists = Course::withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('code', $code)
                ->exists();
        } while ($exists);

        return $code;
    }
}
