<?php

namespace App\Events\Calendar;

use App\Models\Calendar\CalendarEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CalendarEventUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CalendarEvent $calendarEvent
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->calendarEvent->tenant_id . '.calendar'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'calendar.event.updated';
    }

    public function broadcastWith(): array
    {
        $event = $this->calendarEvent->fresh([
            'eventType',
            'creator',
            'participants.user',
            'audiences',
        ]);

        $eventColor = $event->color ?: $event->eventType?->color;
        $canEdit = false;

        return [
            'event' => [
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
                    'participants' => $event->participants->map(function ($participant) {
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
                    })->values()->toArray(),
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
            ],
        ];
    }
}
