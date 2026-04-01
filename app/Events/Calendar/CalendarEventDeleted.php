<?php

namespace App\Events\Calendar;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CalendarEventDeleted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $eventId,
        public string $tenantId
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId . '.calendar'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'calendar.event.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->eventId,
        ];
    }
}
