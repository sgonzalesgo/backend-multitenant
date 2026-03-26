<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupDeleted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $channels,
        public array $payload
    ) {}

    public function broadcastOn(): array
    {
        return array_map(
            fn ($name) => new PrivateChannel($name),
            array_values(array_unique(array_filter($this->channels)))
        );
    }

    public function broadcastAs(): string
    {
        return 'group.deleted';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
