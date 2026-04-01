<?php

namespace App\Repositories\Administration;

use App\Models\Administration\ManualNotification;
use App\Models\Administration\ManualNotificationRecipient;
use App\Models\Administration\User;
use App\Services\Administration\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ManualNotificationRepository
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function paginateForTenant(
        string $tenantId,
        int $perPage = 15,
        string $q = '',
        string $archived = 'without'
    ): LengthAwarePaginator {
        $query = ManualNotification::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'creator:id,name,email',
                'recipients.user:id,name,email',
                'recipients.notification:id,user_id,type,title,message,module,route,payload,read_at,created_at,updated_at',
            ])
            ->withCount('recipients');

        if ($archived === 'only') {
            $query->whereNotNull('archived_at');
        } elseif ($archived === 'without') {
            $query->whereNull('archived_at');
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('message', 'like', "%{$q}%");
            });
        }

        return $query
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function send(
        string $tenantId,
        string $actorId,
        string $actorName,
        array $userIds,
        string $title,
        string $message,
        ?string $route = null,
        ?array $payload = null
    ): array {
        $requestedUserIds = collect($userIds)
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $validUserIds = $this->resolveUsersBelongingToTenant(
            userIds: $requestedUserIds,
            tenantId: $tenantId
        );

        if (empty($validUserIds)) {
            throw new HttpException(422, __('manual_notifications.errors.no_valid_users'));
        }

        $invalidUserIds = array_values(array_diff($requestedUserIds, $validUserIds));

        $basePayload = array_merge([
            'kind' => 'manual',
            'audience' => count($validUserIds) > 1 ? 'multiple' : 'single',
            'sent_by' => $actorId,
            'sent_by_name' => $actorName,
            'source' => 'admin_panel',
        ], $payload ?? []);

        $result = DB::transaction(function () use (
            $tenantId,
            $actorId,
            $title,
            $message,
            $route,
            $validUserIds,
            $basePayload
        ) {
            $manualNotification = ManualNotification::create([
                'tenant_id' => $tenantId,
                'created_by' => $actorId,
                'title' => $title,
                'message' => $message,
                'route' => $route,
                'payload' => $basePayload,
                'sent_at' => now(),
                'archived_at' => null,
            ]);

            $payloadForDelivery = array_merge($basePayload, [
                'manual_notification_id' => (string) $manualNotification->id,
            ]);

            $notifications = $this->notificationService->sendToManyUsers(
                userIds: $validUserIds,
                type: 'admin.manual',
                title: $title,
                message: $message,
                tenantId: $tenantId,
                module: 'admin',
                route: $route,
                payload: $payloadForDelivery
            );

            foreach ($notifications as $notification) {
                ManualNotificationRecipient::create([
                    'manual_notification_id' => (string) $manualNotification->id,
                    'user_id' => (string) $notification->user_id,
                    'notification_id' => (string) $notification->id,
                ]);
            }

            return [
                'manual_notification' => $manualNotification,
                'notifications' => $notifications,
            ];
        });

        return [
            'manual_notification_id' => (string) $result['manual_notification']->id,
            'requested_count' => count($requestedUserIds),
            'sent_count' => count($result['notifications']),
            'user_ids' => $validUserIds,
            'ignored_user_ids' => $invalidUserIds,
        ];
    }

    public function archive(string $tenantId, string $manualNotificationId): array
    {
        $manualNotification = $this->findForTenantOrFail($tenantId, $manualNotificationId);

        if ($manualNotification->archived_at !== null) {
            return [
                'already_archived' => true,
                'id' => (string) $manualNotification->id,
                'archived_at' => optional($manualNotification->archived_at)->toIso8601String(),
            ];
        }

        $manualNotification->update([
            'archived_at' => now(),
        ]);

        return [
            'already_archived' => false,
            'id' => (string) $manualNotification->id,
            'archived_at' => optional($manualNotification->fresh()->archived_at)->toIso8601String(),
        ];
    }

    public function unarchive(string $tenantId, string $manualNotificationId): array
    {
        $manualNotification = $this->findForTenantOrFail($tenantId, $manualNotificationId);

        if ($manualNotification->archived_at === null) {
            return [
                'already_active' => true,
                'id' => (string) $manualNotification->id,
                'archived_at' => null,
            ];
        }

        $manualNotification->update([
            'archived_at' => null,
        ]);

        return [
            'already_active' => false,
            'id' => (string) $manualNotification->id,
            'archived_at' => null,
        ];
    }

    private function findForTenantOrFail(string $tenantId, string $manualNotificationId): ManualNotification
    {
        $manualNotification = ManualNotification::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $manualNotificationId)
            ->first();

        if (! $manualNotification) {
            throw new HttpException(404, __('manual_notifications.errors.not_found'));
        }

        return $manualNotification;
    }

    private function resolveUsersBelongingToTenant(array $userIds, string $tenantId): array
    {
        $teamFk = config('permission.team_foreign_key', 'tenant_id');
        $table = config('permission.table_names.model_has_roles', 'model_has_roles');

        return DB::table($table)
            ->where('model_type', User::class)
            ->whereIn('model_id', $userIds)
            ->where($teamFk, $tenantId)
            ->pluck('model_id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }
}
