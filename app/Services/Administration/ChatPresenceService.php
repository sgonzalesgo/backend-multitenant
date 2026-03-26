<?php

namespace App\Services\Administration;

use Illuminate\Support\Facades\Redis;

class ChatPresenceService
{
    private function key(string $tenantId, string $userId): string
    {
        return "chat:active:tenant:{$tenantId}:user:{$userId}";
    }

    public function setActiveConversation(
        string $tenantId,
        string $userId,
        string $kind,
        string $id
    ): void {
        Redis::setex(
            $this->key($tenantId, $userId),
            120,
            json_encode([
                'kind' => $kind,
                'id' => $id,
                'updated_at' => now()->toIso8601String(),
            ])
        );
    }

    public function clearActiveConversation(string $tenantId, string $userId): void
    {
        Redis::del($this->key($tenantId, $userId));
    }

    public function getActiveConversation(string $tenantId, string $userId): ?array
    {
        $value = Redis::get($this->key($tenantId, $userId));

        if (! $value) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function isViewingConversation(
        string $tenantId,
        string $userId,
        string $kind,
        string $id
    ): bool {
        $active = $this->getActiveConversation($tenantId, $userId);

        if (! $active) {
            return false;
        }

        return (string) ($active['kind'] ?? '') === (string) $kind
            && (string) ($active['id'] ?? '') === (string) $id;
    }
}
