<?php

namespace App\Events\Presence;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UserOnline implements ShouldBroadcastNow
{
    public function __construct(public string $userId) {}

    public function broadcastOn(): Channel
    {
        // Canal global privado para “presencia”
        return new PrivateChannel('presence');
    }

    public function broadcastAs(): string
    {
        return 'user.online';
    }

    public function broadcastWith(): array
    {
        return ['user_id' => $this->userId];
    }
}
