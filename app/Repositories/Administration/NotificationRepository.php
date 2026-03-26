<?php

namespace App\Repositories\Administration;

use App\Models\Administration\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class NotificationRepository
{
    public function createForUser(
        string $userId,
        string $type,
        string $title,
        string $message,
        ?string $tenantId = null,
        ?string $module = null,
        ?string $route = null,
        ?array $payload = null
    ): Notification {
        return Notification::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'module' => $module,
            'route' => $route,
            'payload' => $payload,
            'read_at' => null,
        ]);
    }

    public function createManyForUsers(
        array $userIds,
        string $type,
        string $title,
        string $message,
        ?string $tenantId = null,
        ?string $module = null,
        ?string $route = null,
        ?array $payload = null
    ): array {
        $created = [];

        foreach (array_unique($userIds) as $userId) {
            $created[] = $this->createForUser(
                userId: (string) $userId,
                type: $type,
                title: $title,
                message: $message,
                tenantId: $tenantId,
                module: $module,
                route: $route,
                payload: $payload
            );
        }

        return $created;
    }

    public function listUnread(
        string $userId,
        ?string $tenantId = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        return Notification::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function listAll(
        string $userId,
        ?string $tenantId = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        return Notification::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('user_id', $userId)
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function countUnread(string $userId, ?string $tenantId = null): int
    {
        return Notification::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function findForUser(string $notificationId, string $userId, ?string $tenantId = null): ?Notification
    {
        return Notification::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();
    }

    public function markAsRead(
        string $notificationId,
        string $userId,
        ?string $tenantId = null,
        ?Carbon $readAt = null
    ): ?Notification {
        $notification = $this->findForUser($notificationId, $userId, $tenantId);

        if (! $notification) {
            return null;
        }

        if ($notification->read_at === null) {
            $notification->update([
                'read_at' => $readAt ?? now(),
            ]);
        }

        return $notification->fresh();
    }

    public function markAllAsRead(string $userId, ?string $tenantId = null): int
    {
        return Notification::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function deleteForUser(string $notificationId, string $userId, ?string $tenantId = null): bool
    {
        $notification = $this->findForUser($notificationId, $userId, $tenantId);

        if (! $notification) {
            return false;
        }

        return (bool) $notification->delete();
    }

    public function formatForBroadcast(Notification $notification, int $unreadCount): array
    {
        return [
            'id' => (string) $notification->id,
            'tenant_id' => $notification->tenant_id ? (string) $notification->tenant_id : null,
            'user_id' => (string) $notification->user_id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'module' => $notification->module,
            'route' => $notification->route,
            'payload' => $notification->payload,
            'read_at' => optional($notification->read_at)?->toIso8601String(),
            'created_at' => optional($notification->created_at)?->toIso8601String(),
            'updated_at' => optional($notification->updated_at)?->toIso8601String(),
            'unread_count' => $unreadCount,
        ];
    }

    public function markChatNotificationsAsRead(
        string $userId,
        string $tenantId,
        string $kind,
        string $targetId
    ): int {
        $notifications = Notification::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->where('module', 'chat')
            ->get();

        $matchedIds = $notifications
            ->filter(function (Notification $notification) use ($kind, $targetId) {
                $payload = $notification->payload ?? [];

                if (($payload['kind'] ?? null) !== $kind) {
                    return false;
                }

                if ($kind === 'dm') {
                    return (string) ($payload['conversation_id'] ?? '') === (string) $targetId;
                }

                if ($kind === 'group') {
                    return (string) ($payload['group_id'] ?? '') === (string) $targetId;
                }

                return false;
            })
            ->pluck('id')
            ->all();

        if (empty($matchedIds)) {
            return 0;
        }

        return Notification::query()
            ->whereIn('id', $matchedIds)
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
