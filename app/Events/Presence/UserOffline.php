<?php

namespace App\Events\Presence;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UserOffline implements ShouldBroadcastNow
{
    public function __construct(public string $userId) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('presence');
    }

    public function broadcastAs(): string
    {
        return 'user.offline';
    }

    public function broadcastWith(): array
    {
        return ['user_id' => $this->userId];
    }
}
