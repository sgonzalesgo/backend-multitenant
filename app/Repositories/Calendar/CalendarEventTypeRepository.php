<?php

namespace App\Repositories\Calendar;

use App\Models\Administration\User;
use App\Models\Calendar\CalendarEventType;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CalendarEventTypeRepository
{
    public function list(array $filters): array
    {
        $tenantId = $this->currentTenantId();

        $query = CalendarEventType::query()
            ->forTenant($tenantId);

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['is_system']) && $filters['is_system'] !== '') {
            $query->where('is_system', filter_var($filters['is_system'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['category'])) {
            $category = trim((string) $filters['category']);
            $query->where('settings->category', $category);
        }

        if (!empty($filters['name'])) {
            $name = mb_strtolower(trim((string) $filters['name']));

            $query->whereRaw('LOWER(name) LIKE ?', ["{$name}%"]);
        }

        if (!empty($filters['code'])) {
            $code = mb_strtolower(trim((string) $filters['code']));

            $query->whereRaw('LOWER(code) LIKE ?', ["{$code}%"]);
        }

        if (!empty($filters['q'])) {
            $search = mb_strtolower(trim((string) $filters['q']));

            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        $query->orderBy($sortBy, $sortDirection);

        $paginate = (bool) ($filters['paginate'] ?? false);
        $perPage = (int) ($filters['per_page'] ?? 15);

        if ($paginate) {
            $result = $query->paginate($perPage);

            return [
                'data' => collect($result->items())
                    ->map(fn (CalendarEventType $type) => $this->transform($type))
                    ->values()
                    ->toArray(),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                    'from' => $result->firstItem(),
                    'to' => $result->lastItem(),
                ],
            ];
        }

        return $query->get()
            ->map(fn (CalendarEventType $type) => $this->transform($type))
            ->values()
            ->toArray();
    }

    public function store(array $data, User $user): array
    {
        $tenantId = $this->currentTenantId();

        $normalizedCode = $this->normalizeCode($data['code']);

        $exists = CalendarEventType::query()
            ->forTenant($tenantId)
            ->where('code', $normalizedCode)
            ->exists();

        if ($exists) {
            throw new HttpException(422, __('calendar.event_type.code_already_exists'));
        }

        $type = CalendarEventType::query()->create([
            'tenant_id' => $tenantId,
            'code' => $normalizedCode,
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'icon' => $data['icon'] ?? null,
            'is_system' => false,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'settings' => $data['settings'] ?? null,
        ]);

        return $this->transform($type);
    }

    public function show(CalendarEventType $calendarEventType): array
    {
        $this->ensureBelongsToTenant($calendarEventType);

        return $this->transform($calendarEventType);
    }

    public function update(CalendarEventType $calendarEventType, array $data, User $user): array
    {
        $this->ensureBelongsToTenant($calendarEventType);

        if ($calendarEventType->is_system) {
            if (array_key_exists('code', $data) && $this->normalizeCode($data['code']) !== $calendarEventType->code) {
                throw new HttpException(403, __('calendar.event_type.system_code_cannot_be_modified'));
            }

            if (array_key_exists('name', $data) && trim((string) $data['name']) !== $calendarEventType->name) {
                throw new HttpException(403, __('calendar.event_type.system_name_cannot_be_modified'));
            }
        }

        if (array_key_exists('code', $data)) {
            $normalizedCode = $this->normalizeCode($data['code']);

            $exists = CalendarEventType::query()
                ->forTenant($this->currentTenantId())
                ->where('code', $normalizedCode)
                ->where('id', '!=', $calendarEventType->id)
                ->exists();

            if ($exists) {
                throw new HttpException(422, __('calendar.event_type.code_already_exists'));
            }

            $calendarEventType->code = $normalizedCode;
        }

        if (array_key_exists('name', $data)) {
            $calendarEventType->name = trim((string) $data['name']);
        }

        if (array_key_exists('description', $data)) {
            $calendarEventType->description = $data['description'];
        }

        if (array_key_exists('color', $data)) {
            $calendarEventType->color = $data['color'];
        }

        if (array_key_exists('icon', $data)) {
            $calendarEventType->icon = $data['icon'];
        }

        if (array_key_exists('is_active', $data)) {
            $calendarEventType->is_active = (bool) $data['is_active'];
        }

        if (array_key_exists('settings', $data)) {
            $calendarEventType->settings = $data['settings'];
        }

        $calendarEventType->save();

        return $this->transform($calendarEventType->fresh());
    }

    public function delete(CalendarEventType $calendarEventType, User $user): array
    {
        $this->ensureBelongsToTenant($calendarEventType);

        if ($calendarEventType->is_system) {
            throw new HttpException(403, __('calendar.event_type.system_cannot_be_deleted'));
        }

        $isUsed = $calendarEventType->events()->exists();

        if ($isUsed) {
            throw new HttpException(422, __('calendar.event_type.in_use_cannot_be_deleted'));
        }

        $calendarEventType->delete();

        return [
            'message' => __('calendar.event_type.deleted_successfully'),
            'id' => (string) $calendarEventType->id,
        ];
    }

    protected function currentTenantId(): string
    {
        $tenant = app('currentTenant');

        if (!$tenant) {
            throw new HttpException(422, __('calendar.event.no_current_tenant'));
        }

        return (string) $tenant->id;
    }

    protected function ensureBelongsToTenant(CalendarEventType $calendarEventType): void
    {
        if ((string) $calendarEventType->tenant_id !== $this->currentTenantId()) {
            throw new HttpException(404, __('calendar.event_type.not_found'));
        }
    }

    protected function normalizeCode(string $code): string
    {
        return Str::of($code)
            ->trim()
            ->lower()
            ->replace(' ', '_')
            ->replace('-', '_')
            ->__toString();
    }

    protected function transform(CalendarEventType $type): array
    {
        return [
            'id' => (string) $type->id,
            'tenant_id' => (string) $type->tenant_id,
            'code' => $type->code,
            'name' => $type->name,
            'description' => $type->description,
            'color' => $type->color,
            'icon' => $type->icon,
            'is_system' => (bool) $type->is_system,
            'is_active' => (bool) $type->is_active,
            'settings' => $type->settings,
            'created_at' => optional($type->created_at)?->toIso8601String(),
            'updated_at' => optional($type->updated_at)?->toIso8601String(),
        ];
    }
}
