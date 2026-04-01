<?php

namespace App\Repositories\Calendar;

use App\Events\Calendar\CalendarEventCreated;
use App\Events\Calendar\CalendarEventDeleted;
use App\Events\Calendar\CalendarEventUpdated;
use App\Models\Administration\User;
use App\Models\Calendar\CalendarEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CalendarEventRepository
{
    public function list(array $filters, User $user): array
    {
        $tenantId = $this->currentTenantId();

        $query = CalendarEvent::query()
            ->with([
                'eventType',
                'creator',
                'participants.user',
                'audiences',
            ])
            ->forTenant($tenantId)
            ->where(function ($q) use ($user) {
                $q->where('visibility', 'public_tenant')
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('participants', function ($participantQuery) use ($user) {
                        $participantQuery->where('user_id', $user->id);
                    });
            });

        if (!empty($filters['start']) && !empty($filters['end'])) {
            $query->betweenDates($filters['start'], $filters['end']);
        }

        if (!empty($filters['event_type_id'])) {
            $query->where('event_type_id', $filters['event_type_id']);
        }

        if (!empty($filters['created_by_me'])) {
            $query->createdBy($user->id);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('location', 'ilike', "%{$search}%");
            });
        }

        return $query
            ->orderBy('start_at')
            ->get()
            ->map(fn (CalendarEvent $event) => $this->transformEvent($event, (string) $user->id))
            ->values()
            ->toArray();
    }

    public function store(array $data, User $user): array
    {
        $tenantId = $this->currentTenantId();

        $event = DB::transaction(function () use ($data, $user, $tenantId) {
            /** @var CalendarEvent $event */
            $event = CalendarEvent::create([
                'tenant_id' => $tenantId,
                'event_type_id' => $data['event_type_id'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'url' => $data['url'] ?? null,
                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'] ?? null,
                'all_day' => (bool) $data['all_day'],
                'timezone' => $data['timezone'] ?? 'America/New_York',
                'status' => $data['status'] ?? 'confirmed',
                'visibility' => $data['visibility'] ?? 'restricted',
                'source' => 'internal',
                'editable_by' => $data['editable_by'] ?? 'creator_only',
                'color' => $data['color'] ?? null,
                'is_recurring' => (bool) ($data['is_recurring'] ?? false),
                'recurrence_rule' => $data['recurrence_rule'] ?? null,
                'google_sync_enabled' => (bool) ($data['google_sync_enabled'] ?? false),
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->syncParticipants($event, $data['participants'] ?? [], $tenantId, $user, false);
            $this->syncAudiences($event, $data['audiences'] ?? [], $tenantId);

            return $event->load([
                'eventType',
                'creator',
                'participants.user',
                'audiences',
            ]);
        });

        broadcast(new CalendarEventCreated($event))->toOthers();

        return $this->transformEvent($event, (string) $user->id);
    }

    public function show(CalendarEvent $calendarEvent, User $user): array
    {
        $this->ensureBelongsToTenant($calendarEvent);

        if (!$this->canUserViewEvent($calendarEvent, $user)) {
            throw new HttpException(404, __('calendar.event.not_found'));
        }

        $calendarEvent->load([
            'eventType',
            'creator',
            'participants.user',
            'audiences',
        ]);

        return $this->transformEvent($calendarEvent, (string) $user->id);
    }

    public function update(CalendarEvent $calendarEvent, array $data, User $user): array
    {
        $this->ensureBelongsToTenant($calendarEvent);
        $this->ensureCanModify($calendarEvent, $user);

        $tenantId = $this->currentTenantId();

        $calendarEvent = DB::transaction(function () use ($calendarEvent, $data, $user, $tenantId) {
            $calendarEvent->fill([
                'event_type_id' => array_key_exists('event_type_id', $data) ? $data['event_type_id'] : $calendarEvent->event_type_id,
                'title' => array_key_exists('title', $data) ? $data['title'] : $calendarEvent->title,
                'description' => array_key_exists('description', $data) ? $data['description'] : $calendarEvent->description,
                'location' => array_key_exists('location', $data) ? $data['location'] : $calendarEvent->location,
                'url' => array_key_exists('url', $data) ? $data['url'] : $calendarEvent->url,
                'start_at' => array_key_exists('start_at', $data) ? $data['start_at'] : $calendarEvent->start_at,
                'end_at' => array_key_exists('end_at', $data) ? $data['end_at'] : $calendarEvent->end_at,
                'all_day' => array_key_exists('all_day', $data) ? (bool) $data['all_day'] : $calendarEvent->all_day,
                'timezone' => array_key_exists('timezone', $data) ? $data['timezone'] : $calendarEvent->timezone,
                'status' => array_key_exists('status', $data) ? $data['status'] : $calendarEvent->status,
                'visibility' => array_key_exists('visibility', $data) ? $data['visibility'] : $calendarEvent->visibility,
                'editable_by' => array_key_exists('editable_by', $data) ? $data['editable_by'] : $calendarEvent->editable_by,
                'color' => array_key_exists('color', $data) ? $data['color'] : $calendarEvent->color,
                'is_recurring' => array_key_exists('is_recurring', $data) ? (bool) $data['is_recurring'] : $calendarEvent->is_recurring,
                'recurrence_rule' => array_key_exists('recurrence_rule', $data) ? $data['recurrence_rule'] : $calendarEvent->recurrence_rule,
                'google_sync_enabled' => array_key_exists('google_sync_enabled', $data) ? (bool) $data['google_sync_enabled'] : $calendarEvent->google_sync_enabled,
                'metadata' => array_key_exists('metadata', $data) ? $data['metadata'] : $calendarEvent->metadata,
                'updated_by' => $user->id,
            ]);

            $calendarEvent->save();

            if (array_key_exists('participants', $data)) {
                $this->syncParticipants($calendarEvent, $data['participants'] ?? [], $tenantId, $user, true);
            }

            if (array_key_exists('audiences', $data)) {
                $this->syncAudiences($calendarEvent, $data['audiences'] ?? [], $tenantId);
            }

            return $calendarEvent->load([
                'eventType',
                'creator',
                'participants.user',
                'audiences',
            ]);
        });

        broadcast(new CalendarEventUpdated($calendarEvent))->toOthers();

        return $this->transformEvent($calendarEvent, (string) $user->id);
    }

    public function delete(CalendarEvent $calendarEvent, User $user): array
    {
        $this->ensureBelongsToTenant($calendarEvent);
        $this->ensureCanModify($calendarEvent, $user);

        $eventId = (string) $calendarEvent->id;
        $tenantId = (string) $calendarEvent->tenant_id;

        DB::transaction(function () use ($calendarEvent) {
            $calendarEvent->delete();
        });

        broadcast(new CalendarEventDeleted($eventId, $tenantId))->toOthers();

        return [
            'message' => __('calendar.event.deleted_successfully'),
            'id' => $eventId,
        ];
    }

    protected function syncParticipants(CalendarEvent $event, array $participants, string $tenantId, User $authUser, bool $isUpdate): void {
        if ($isUpdate) {
            $event->participants()->delete();
        }

        $ownerAlreadyIncluded = collect($participants)->contains(function (array $participant) use ($authUser) {
            return !empty($participant['user_id']) && (string) $participant['user_id'] === (string) $authUser->id;
        });

        $event->participants()->create([
            'tenant_id' => $tenantId,
            'user_id' => $authUser->id,
            'person_id' => null,
            'participant_type' => 'user',
            'role' => 'owner',
            'response_status' => 'accepted',
            'is_required' => true,
            'can_view' => true,
            'can_receive_notifications' => true,
        ]);

        foreach ($participants as $participant) {
            if (
                $ownerAlreadyIncluded &&
                !empty($participant['user_id']) &&
                (string) $participant['user_id'] === (string) $authUser->id
            ) {
                continue;
            }

            $event->participants()->create([
                'tenant_id' => $tenantId,
                'user_id' => $participant['user_id'] ?? null,
                'person_id' => $participant['person_id'] ?? null,
                'participant_type' => $participant['participant_type'],
                'role' => $participant['role'] ?? 'attendee',
                'response_status' => $participant['response_status'] ?? 'pending',
                'is_required' => (bool) ($participant['is_required'] ?? false),
                'can_view' => (bool) ($participant['can_view'] ?? true),
                'can_receive_notifications' => (bool) ($participant['can_receive_notifications'] ?? true),
            ]);
        }
    }

    protected function syncAudiences(CalendarEvent $event, array $audiences, string $tenantId): void
    {
        $event->audiences()->delete();

        foreach ($audiences as $audience) {
            $event->audiences()->create([
                'tenant_id' => $tenantId,
                'audience_type' => $audience['audience_type'],
                'audience_id' => $audience['audience_id'] ?? null,
                'filters' => $audience['filters'] ?? null,
            ]);
        }
    }

    protected function currentTenantId(): string
    {
        $tenant = app('currentTenant');

        if (!$tenant) {
            throw new HttpException(422, __('calendar.event.no_current_tenant'));
        }

        return (string) $tenant->id;
    }

    protected function ensureBelongsToTenant(CalendarEvent $calendarEvent): void
    {
        if ((string) $calendarEvent->tenant_id !== $this->currentTenantId()) {
            throw new HttpException(404, __('calendar.event.not_found'));
        }
    }

    protected function ensureCanModify(CalendarEvent $calendarEvent, User $user): void
    {
        if (!$this->canUserEditEvent($calendarEvent, $user)) {
            throw new HttpException(403, __('calendar.event.not_allowed'));
        }
    }

    protected function transformEvent(CalendarEvent $event, string $authUserId): array
    {
        $authUser = auth()->user();
        $canEdit = $authUser ? $this->canUserEditEvent($event, $authUser) : false;
        $eventColor = $event->color ?: $event->eventType?->color;

        return [
            'id' => (string) $event->id,
            'title' => $event->title,
            'start' => optional($event->start_at)?->toIso8601String(),
            'end' => optional($event->end_at)?->toIso8601String(),
            'allDay' => (bool) $event->all_day,
            'url' => $event->url,
            'editable' => $canEdit,
            'startEditable' => $canEdit,
            'durationEditable' => $canEdit,
            'backgroundColor' => $eventColor,
            'borderColor' => $eventColor,
            'extendedProps' => [
                'description' => $event->description,
                'location' => $event->location,
                'timezone' => $event->timezone,
                'status' => $event->status,
                'visibility' => $event->visibility,
                'source' => $event->source,
                'editable_by' => $event->editable_by,
                'color' => $event->color,
                'is_recurring' => (bool) $event->is_recurring,
                'recurrence_rule' => $event->recurrence_rule,
                'google_sync_enabled' => (bool) $event->google_sync_enabled,
                'google_last_synced_at' => optional($event->google_last_synced_at)?->toIso8601String(),
                'metadata' => $event->metadata,
                'event_type' => $event->eventType ? [
                    'id' => (string) $event->eventType->id,
                    'code' => $event->eventType->code,
                    'name' => $event->eventType->name,
                    'color' => $event->eventType->color,
                    'icon' => $event->eventType->icon,
                ] : null,
                'creator' => $event->creator ? [
                    'id' => (string) $event->creator->id,
                    'name' => $event->creator->name ?? null,
                    'email' => $event->creator->email ?? null,
                ] : null,
                'participants' => $this->transformParticipants($event->participants),
                'audiences' => $event->audiences->map(function ($audience) {
                    return [
                        'id' => (string) $audience->id,
                        'audience_type' => $audience->audience_type,
                        'audience_id' => $audience->audience_id ? (string) $audience->audience_id : null,
                        'filters' => $audience->filters,
                    ];
                })->values()->toArray(),
                'permissions' => [
                    'can_edit' => $canEdit,
                    'can_delete' => $canEdit,
                ],
            ],
        ];
    }

    protected function transformParticipants(Collection $participants): array
    {
        return $participants->map(function ($participant) {
            return [
                'id' => (string) $participant->id,
                'user_id' => $participant->user_id ? (string) $participant->user_id : null,
                'person_id' => $participant->person_id ? (string) $participant->person_id : null,
                'participant_type' => $participant->participant_type,
                'role' => $participant->role,
                'response_status' => $participant->response_status,
                'is_required' => (bool) $participant->is_required,
                'can_view' => (bool) $participant->can_view,
                'can_receive_notifications' => (bool) $participant->can_receive_notifications,
                'user' => $participant->user ? [
                    'id' => (string) $participant->user->id,
                    'name' => $participant->user->name ?? null,
                    'email' => $participant->user->email ?? null,
                ] : null,
            ];
        })->values()->toArray();
    }

    protected function canUserEditEvent(CalendarEvent $event, User $user): bool
    {
        if ((string) $event->created_by === (string) $user->id) {
            return true;
        }

        return $event->participants()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'organizer'])
            ->exists();
    }

    protected function canUserViewEvent(CalendarEvent $event, User $user): bool
    {
        if ((string) $event->created_by === (string) $user->id) {
            return true;
        }

        $isDirectParticipant = $event->participants()
            ->where('user_id', $user->id)
            ->exists();

        if ($isDirectParticipant) {
            return true;
        }

        if ($event->visibility === 'public_tenant') {
            return true;
        }

        if ($event->visibility === 'private') {
            return false;
        }

        if ($event->visibility === 'restricted') {
            // por ahora, si no estás manejando aún audiencias reales por usuario,
            // solo participantes directos pueden verlo.
            // Luego aquí puedes extender a audiencias (section, grade, role, etc.)
            return false;
        }

        return false;
    }
}
