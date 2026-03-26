<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Administration\Tenant;
use App\Repositories\Administration\NotificationRepository;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationRepository $notifications
    ) {}

    /**
     * Lista notificaciones no leídas del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $perPage = (int) $request->query('per_page', 20);

        $result = $this->notifications->listUnread(
            userId: (string) $request->user()->id,
            tenantId: (string) $tenant->id,
            perPage: $perPage
        );

        return response()->json($result);
    }

    /**
     * Devuelve solo el conteo de no leídas.
     */
    public function countUnread(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $count = $this->notifications->countUnread(
            userId: (string) $request->user()->id,
            tenantId: (string) $tenant->id
        );

        return response()->json([
            'data' => [
                'unread_count' => $count,
            ]
        ]);
    }

    /**
     * Marca una notificación como leída.
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $notification = $this->notifications->markAsRead(
            notificationId: $notificationId,
            userId: (string) $request->user()->id,
            tenantId: (string) $tenant->id
        );

        abort_unless($notification, 404, 'Notificación no encontrada.');

        $unreadCount = $this->notifications->countUnread(
            userId: (string) $request->user()->id,
            tenantId: (string) $tenant->id
        );

        return response()->json([
            'data' => [
                'notification' => $notification,
                'unread_count' => $unreadCount,
            ]
        ]);
    }

    /**
     * Marca todas las notificaciones del usuario como leídas.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $updated = $this->notifications->markAllAsRead(
            userId: (string) $request->user()->id,
            tenantId: (string) $tenant->id
        );

        return response()->json([
            'data' => [
                'updated' => $updated,
                'unread_count' => 0,
            ]
        ]);
    }

    public function markChatAsRead(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $data = $request->validate([
            'kind' => ['required', 'string', 'in:dm,group'],
            'id' => ['required', 'string'],
        ]);

        $updated = $this->notifications->markChatNotificationsAsRead(
            userId: (string) $request->user()->id,
            tenantId: (string) $tenant->id,
            kind: $data['kind'],
            targetId: $data['id']
        );

        $unreadCount = $this->notifications->countUnread(
            userId: (string) $request->user()->id,
            tenantId: (string) $tenant->id
        );

        return response()->json([
            'data' => [
                'updated' => $updated,
                'unread_count' => $unreadCount,
            ]
        ]);
    }
}
