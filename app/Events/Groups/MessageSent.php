<?php

namespace App\Events\Groups;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;

class MessageSent implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        public string $groupId,
        public array $message
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("group.{$this->groupId}");
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'group_id' => $this->groupId,
            'message' => $this->message,
        ];
    }
}
