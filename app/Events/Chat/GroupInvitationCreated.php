<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupInvitationCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $tenantId,
        public string $userId,
        public array $payload
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("inbox.tenant.{$this->tenantId}.user.{$this->userId}")
        ];
    }

    public function broadcastAs(): string
    {
        return 'group.invitation.created';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
