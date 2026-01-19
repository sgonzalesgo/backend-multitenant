<?php

namespace App\Events\Presence;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class UserPresenceChanged implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        public int $userId,
        public bool $online,
        public int $groupId
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("group.{$this->groupId}");
    }

    public function broadcastAs(): string
    {
        return 'presence.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'online' => $this->online,
            'group_id' => $this->groupId,
        ];
    }
}
