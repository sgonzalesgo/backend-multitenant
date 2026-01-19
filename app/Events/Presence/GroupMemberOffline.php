<?php

namespace App\Events\Presence;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class GroupMemberOffline implements ShouldBroadcastNow
{
    public function __construct(
        public int $groupId,
        public string $userId
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("group.{$this->groupId}");
    }

    public function broadcastAs(): string
    {
        return 'member.offline';
    }

    public function broadcastWith(): array
    {
        return ['user_id' => $this->userId];
    }
}
