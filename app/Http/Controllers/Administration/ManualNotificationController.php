<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\Notification\ListManualNotificationRequest;
use App\Http\Requests\Administration\Notification\SendManualNotificationRequest;
use App\Models\Administration\Tenant;
use App\Repositories\Administration\ManualNotificationRepository;
use Illuminate\Http\JsonResponse;

class ManualNotificationController extends Controller
{
    public function __construct(
        private readonly ManualNotificationRepository $repo
    ) {}

    public function index(ListManualNotificationRequest $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, __('manual_notifications.errors.tenant_not_initialized'));

        $result = $this->repo->paginateForTenant(
            tenantId: (string) $tenant->id,
            perPage: (int) $request->validated('per_page', 15),
            q: trim((string) $request->validated('q', '')),
            archived: (string) $request->validated('archived', 'without')
        );

        return response()->json($result);
    }

    public function send(SendManualNotificationRequest $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, __('manual_notifications.errors.tenant_not_initialized'));

        $actor = $request->user();
        $data = $request->validated();

        $result = $this->repo->send(
            tenantId: (string) $tenant->id,
            actorId: (string) $actor->id,
            actorName: (string) ($actor->name ?? __('manual_notifications.labels.default_user')),
            userIds: $data['user_ids'],
            title: $data['title'],
            message: $data['message'],
            route: $data['route'] ?? null,
            payload: $data['payload'] ?? []
        );

        return response()->json([
            'message' => $result['sent_count'] === 1
                ? __('manual_notifications.messages.sent_one')
                : __('manual_notifications.messages.sent_many'),
            'data' => $result,
        ], 201);
    }

    public function archive(string $manualNotificationId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, __('manual_notifications.errors.tenant_not_initialized'));

        $result = $this->repo->archive(
            tenantId: (string) $tenant->id,
            manualNotificationId: $manualNotificationId
        );

        return response()->json([
            'message' => $result['already_archived']
                ? __('manual_notifications.messages.already_archived')
                : __('manual_notifications.messages.archived'),
            'data' => [
                'id' => $result['id'],
                'archived_at' => $result['archived_at'],
            ],
        ]);
    }

    public function unarchive(string $manualNotificationId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, __('manual_notifications.errors.tenant_not_initialized'));

        $result = $this->repo->unarchive(
            tenantId: (string) $tenant->id,
            manualNotificationId: $manualNotificationId
        );

        return response()->json([
            'message' => $result['already_active']
                ? __('manual_notifications.messages.already_active')
                : __('manual_notifications.messages.unarchived'),
            'data' => [
                'id' => $result['id'],
                'archived_at' => $result['archived_at'],
            ],
        ]);
    }
}
