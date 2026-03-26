<?php

namespace App\Services\Administration;

use App\Events\Notification\NotificationCreated;
use App\Models\Administration\Notification;
use App\Repositories\Administration\NotificationRepository;

readonly class NotificationService
{
    public function __construct(
        private NotificationRepository $notifications,
        private ChatPresenceService    $chatPresence
    ) {}

    public function sendToUser(
        string $userId,
        string $type,
        string $title,
        string $message,
        ?string $tenantId = null,
        ?string $module = null,
        ?string $route = null,
        ?array $payload = null
    ): ?Notification {
        if (
            $module === 'chat' &&
            $tenantId &&
            is_array($payload) &&
            isset($payload['kind'])
        ) {
            $kind = (string) $payload['kind'];

            if ($kind === 'dm' && ! empty($payload['conversation_id'])) {
                if ($this->chatPresence->isViewingConversation(
                    tenantId: $tenantId,
                    userId: $userId,
                    kind: 'dm',
                    id: (string) $payload['conversation_id']
                )) {
                    return null;
                }
            }

            if ($kind === 'group' && ! empty($payload['group_id'])) {
                if ($this->chatPresence->isViewingConversation(
                    tenantId: $tenantId,
                    userId: $userId,
                    kind: 'group',
                    id: (string) $payload['group_id']
                )) {
                    return null;
                }
            }
        }

        $notification = $this->notifications->createForUser(
            userId: $userId,
            type: $type,
            title: $title,
            message: $message,
            tenantId: $tenantId,
            module: $module,
            route: $route,
            payload: $payload
        );

        $unreadCount = $this->notifications->countUnread(
            userId: $userId,
            tenantId: $tenantId
        );

        broadcast(new NotificationCreated(
            userId: $userId,
            payload: $this->notifications->formatForBroadcast($notification, $unreadCount)
        ));

        return $notification;
    }

    public function sendToManyUsers(
        array $userIds,
        string $type,
        string $title,
        string $message,
        ?string $tenantId = null,
        ?string $module = null,
        ?string $route = null,
        ?array $payload = null,
        array $excludeUserIds = []
    ): array {
        $created = [];
        $excluded = array_map('strval', $excludeUserIds);

        foreach (array_unique(array_map('strval', $userIds)) as $userId) {
            if (in_array($userId, $excluded, true)) {
                continue;
            }

            $notification = $this->sendToUser(
                userId: $userId,
                type: $type,
                title: $title,
                message: $message,
                tenantId: $tenantId,
                module: $module,
                route: $route,
                payload: $payload
            );

            if ($notification) {
                $created[] = $notification;
            }
        }

        return $created;
    }
}
