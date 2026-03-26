<?php

namespace App\Http\Controllers\Administration;

// global import
use App\Events\Chat\ChatMessageDeleted;
use App\Events\Chat\ChatMessageUpdated;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

// local import
use App\Events\Chat\GroupDeleted;
use App\Events\Chat\GroupUpdated;
use App\Http\Controllers\Controller;
use App\Models\Administration\Tenant;
use App\Events\Chat\GroupMemberRemoved;
use App\Events\Chat\ChatMessageCreated;
use App\Events\Chat\ChatConversationRead;
use App\Models\Administration\Chat\Group;
use App\Events\Chat\GroupInvitationCreated;
use App\Events\Chat\GroupInvitationAccepted;
use App\Events\Chat\GroupInvitationRejected;
use App\Repositories\Administration\AuthRepository;
use App\Repositories\Administration\GroupRepository;
use App\Repositories\Administration\GroupMessageRepository;
use App\Services\Administration\NotificationService;

class GroupController extends Controller
{
    public function __construct(
        private readonly AuthRepository $authRepo,
        private readonly GroupRepository $groups,
        private readonly GroupMessageRepository $messages,
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $user = $request->user();
        $sidebar = (bool) $request->query('sidebar', false);

        if ($sidebar) {
            $rows = $this->groups->listMyGroupsWithUnread((string) $tenant->id, (string) $user->id);

            return response()->json(['data' => $rows]);
        }

        $perPage = (int) $request->query('per_page', 15);
        $q = trim((string) $request->query('q', ''));

        $result = $this->groups->paginateVisibleGroups(
            tenantId: (string) $tenant->id,
            userId: (string) $user->id,
            perPage: $perPage,
            q: $q
        );

        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $user = $request->user();

        $group = $this->groups->createGroup(
            tenantId: (string) $tenant->id,
            ownerId: (string) $user->id,
            name: $data['name']
        );

        return response()->json(['data' => $group], 201);
    }

    public function invite(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $data = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $actor = $request->user();
        $tenantId = (string) $tenant->id;
        $groupId = (string) $group->id;
        $actorId = (string) $actor->id;
        $targetUserId = (string) $data['user_id'];

        $this->groups->inviteUserToGroup(
            tenantId: $tenantId,
            groupId: $groupId,
            groupOwnerId: (string) $group->owner_id,
            actorId: $actorId,
            targetUserId: $targetUserId
        );

        broadcast(new GroupInvitationCreated(
            tenantId: $tenantId,
            userId: $targetUserId,
            payload: [
                'group_id' => $groupId,
                'group_name' => (string) $group->name,
                'owner_id' => (string) $group->owner_id,
                'invited_by' => $actorId,
                'invited_at' => now()->toIso8601String(),
                'status' => 'invited',
            ]
        ));

        return response()->json(['message' => 'Invitación enviada.']);
    }

    public function invitations(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $user = $request->user();

        $rows = $this->groups->myInvitations(
            tenantId: (string) $tenant->id,
            userId: (string) $user->id
        );

        return response()->json(['data' => $rows]);
    }

    public function accept(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();
        $tenantId = (string) $tenant->id;
        $groupId = (string) $group->id;
        $userId = (string) $user->id;
        $ownerId = (string) $group->owner_id;

        $this->groups->acceptInvitation(
            tenantId: $tenantId,
            groupId: $groupId,
            userId: $userId
        );

        $channels = [
            "inbox.tenant.{$tenantId}.user.{$userId}",
            "inbox.tenant.{$tenantId}.user.{$ownerId}",
        ];

        broadcast(new GroupInvitationAccepted(
            channels: $channels,
            payload: [
                'group_id' => $groupId,
                'group_name' => (string) $group->name,
                'user_id' => $userId,
                'user_name' => (string) $user->name,
                'owner_id' => $ownerId,
                'status' => 'accepted',
                'responded_at' => now()->toIso8601String(),
            ]
        ));

        return response()->json(['message' => 'Invitación aceptada.']);
    }

    public function reject(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();
        $tenantId = (string) $tenant->id;
        $groupId = (string) $group->id;
        $userId = (string) $user->id;
        $ownerId = (string) $group->owner_id;

        $this->groups->rejectInvitation(
            tenantId: $tenantId,
            groupId: $groupId,
            userId: $userId
        );

        $channels = [
            "inbox.tenant.{$tenantId}.user.{$userId}",
            "inbox.tenant.{$tenantId}.user.{$ownerId}",
        ];

        broadcast(new GroupInvitationRejected(
            channels: $channels,
            payload: [
                'group_id' => $groupId,
                'group_name' => (string) $group->name,
                'user_id' => $userId,
                'user_name' => (string) $user->name,
                'owner_id' => $ownerId,
                'status' => 'rejected',
                'responded_at' => now()->toIso8601String(),
            ]
        ));

        return response()->json(['message' => 'Invitación rechazada.']);
    }

    public function members(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();

        $members = $this->groups->acceptedMembers(
            tenantId: (string) $tenant->id,
            groupId: (string) $group->id,
            requesterId: (string) $user->id,
            groupOwnerId: (string) $group->owner_id
        );

        $data = collect($members)->map(function ($m) use ($tenant) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'online' => $this->authRepo->isOnline((string) $tenant->id, (string) $m->id),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function messages(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();
        $this->groups->assertAcceptedOrOwner(
            (string) $tenant->id,
            (string) $group->id,
            (string) $user->id,
            (string) $group->owner_id
        );

        $perPage = (int) $request->query('per_page', 30);

        return response()->json(
            $this->messages->paginateMessages((string) $tenant->id, (string) $group->id, $perPage)
        );
    }

    public function sendMessage(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();
        $this->groups->assertAcceptedOrOwner(
            (string) $tenant->id,
            (string) $group->id,
            (string) $user->id,
            (string) $group->owner_id
        );

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $tenantId = (string) $tenant->id;
        $groupId = (string) $group->id;
        $senderId = (string) $user->id;
        $senderName = (string) ($user->name ?? 'Usuario');
        $groupName = (string) ($group->name ?? 'Grupo');

        $message = $this->messages->createMessage(
            tenantId: $tenantId,
            groupId: $groupId,
            userId: $senderId,
            body: $data['body']
        );

        $payload = [
            'type' => 'group',
            'tenant_id' => $tenantId,
            'group_id' => $groupId,
            'message' => $message,
        ];

        $channels = [
            "group.{$groupId}",
        ];

        broadcast(new ChatMessageCreated($channels, $payload))->toOthers();

        $memberIds = collect($this->groups->acceptedMembers(
            tenantId: $tenantId,
            groupId: $groupId,
            requesterId: $senderId,
            groupOwnerId: (string) $group->owner_id
        ))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $preview = trim((string) $data['body']);
        $preview = mb_strimwidth($preview, 0, 120, '...');

        $this->notificationService->sendToManyUsers(
            userIds: $memberIds,
            excludeUserIds: [$senderId],
            tenantId: $tenantId,
            type: 'chat.group.message',
            title: 'Nuevo mensaje en grupo',
            message: "{$senderName} envió un mensaje en {$groupName}: {$preview}",
            module: 'chat',
            route: 'apps/chat',
            payload: [
                'kind' => 'group',
                'group_id' => $groupId,
                'group_name' => $groupName,
                'sender_id' => $senderId,
                'sender_name' => $senderName,
                'message_id' => is_array($message) ? ($message['id'] ?? null) : ($message->id ?? null),
            ]
        );

        return response()->json(['data' => $message], 201);
    }

    public function read(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();

        $this->groups->assertAcceptedOrOwner(
            (string) $tenant->id,
            (string) $group->id,
            (string) $user->id,
            (string) $group->owner_id
        );

        $tenantId = (string) $tenant->id;
        $groupId = (string) $group->id;
        $userId = (string) $user->id;

        $lastReadAt = $this->groups->markReadAt($tenantId, $groupId, $userId);

        $payload = [
            'type' => 'group',
            'tenant_id' => $tenantId,
            'group_id' => $groupId,
            'user_id' => $userId,
            'last_read_at' => $lastReadAt,
        ];

        $channels = [
            "group.{$groupId}",
        ];

        broadcast(new ChatConversationRead($channels, $payload))->toOthers();

        return response()->json([
            'group_id' => $groupId,
            'user_id' => $userId,
            'last_read_at' => $lastReadAt,
        ]);
    }

    public function update(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();
        abort_unless((string) $group->owner_id === (string) $user->id, 403, 'Solo el owner puede editar el grupo.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $tenantId = (string) $tenant->id;
        $groupId = (string) $group->id;
        $ownerId = (string) $group->owner_id;
        $updatedBy = (string) $user->id;

        $userIds = $this->groups->getGroupUserIdsForRealtime(
            tenantId: $tenantId,
            groupId: $groupId,
            ownerId: $ownerId
        );

        $updated = $this->groups->updateGroup(
            tenantId: $tenantId,
            groupId: $groupId,
            ownerId: $ownerId,
            name: $data['name']
        );

        $channels = array_map(
            fn ($userId) => "inbox.tenant.{$tenantId}.user.{$userId}",
            $userIds
        );

        broadcast(new GroupUpdated(
            channels: $channels,
            payload: [
                'group_id' => $groupId,
                'group_name' => (string) $updated['name'],
                'owner_id' => $ownerId,
                'updated_by' => $updatedBy,
                'updated_at' => now()->toIso8601String(),
            ]
        ));

        return response()->json(['data' => $updated]);
    }

    public function destroy(Request $request, Group $group): Response
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();
        abort_unless((string) $group->owner_id === (string) $user->id, 403, 'Solo el owner puede eliminar el grupo.');

        $tenantId = (string) $tenant->id;
        $groupId = (string) $group->id;
        $ownerId = (string) $group->owner_id;
        $deletedBy = (string) $user->id;
        $groupName = (string) $group->name;

        $userIds = $this->groups->getGroupUserIdsForRealtime(
            tenantId: $tenantId,
            groupId: $groupId,
            ownerId: $ownerId
        );

        $this->groups->deleteGroup(
            tenantId: $tenantId,
            groupId: $groupId,
            ownerId: $ownerId
        );

        $channels = array_map(
            fn ($userId) => "inbox.tenant.{$tenantId}.user.{$userId}",
            $userIds
        );

        broadcast(new GroupDeleted(
            channels: $channels,
            payload: [
                'group_id' => $groupId,
                'group_name' => $groupName,
                'owner_id' => $ownerId,
                'deleted_by' => $deletedBy,
                'deleted_at' => now()->toIso8601String(),
            ]
        ));

        return response()->noContent();
    }

    public function leave(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();
        $tenantId = (string) $tenant->id;
        $groupId = (string) $group->id;
        $userId = (string) $user->id;
        $ownerId = (string) $group->owner_id;

        $this->groups->leaveGroup(
            tenantId: $tenantId,
            groupId: $groupId,
            userId: $userId,
            groupOwnerId: $ownerId
        );

        $channels = [
            "inbox.tenant.{$tenantId}.user.{$userId}",
            "inbox.tenant.{$tenantId}.user.{$ownerId}",
            "group.{$groupId}",
        ];

        broadcast(new GroupMemberRemoved(
            channels: $channels,
            payload: [
                'group_id' => $groupId,
                'group_name' => (string) $group->name,
                'user_id' => $userId,
                'user_name' => (string) $user->name,
                'owner_id' => $ownerId,
                'action' => 'left',
                'removed_by' => $userId,
                'removed_at' => now()->toIso8601String(),
            ]
        ));

        return response()->json(['message' => 'Has salido del grupo.']);
    }

    public function removeMember(Request $request, Group $group, string $userId): Response
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $actor = $request->user();
        $tenantId = (string) $tenant->id;
        $groupId = (string) $group->id;
        $ownerId = (string) $group->owner_id;
        $actorId = (string) $actor->id;
        $targetUserId = (string) $userId;

        $this->groups->removeMember(
            tenantId: $tenantId,
            groupId: $groupId,
            ownerId: $actorId,
            targetUserId: $targetUserId,
            groupOwnerId: $ownerId
        );

        $targetUser = DB::table('users')->select(['id', 'name'])->where('id', $targetUserId)->first();

        $channels = [
            "inbox.tenant.{$tenantId}.user.{$targetUserId}",
            "inbox.tenant.{$tenantId}.user.{$ownerId}",
            "group.{$groupId}",
        ];

        broadcast(new GroupMemberRemoved(
            channels: $channels,
            payload: [
                'group_id' => $groupId,
                'group_name' => (string) $group->name,
                'user_id' => $targetUserId,
                'user_name' => (string) ($targetUser?->name ?? 'Usuario'),
                'owner_id' => $ownerId,
                'action' => 'removed',
                'removed_by' => $actorId,
                'removed_at' => now()->toIso8601String(),
            ]
        ));

        return response()->noContent();
    }

    public function updateMessage(Request $request, Group $group, string $messageId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();

        $this->groups->assertAcceptedOrOwner(
            (string) $tenant->id,
            (string) $group->id,
            (string) $user->id,
            (string) $group->owner_id
        );

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $tenantId = (string) $tenant->id;
        $groupId = (string) $group->id;
        $userId = (string) $user->id;

        $message = $this->messages->updateMessage(
            tenantId: $tenantId,
            groupId: $groupId,
            messageId: $messageId,
            userId: $userId,
            body: $data['body']
        );

        $payload = [
            'type' => 'group',
            'tenant_id' => $tenantId,
            'group_id' => $groupId,
            'message_id' => $messageId,
            'message' => $message,
        ];

        $channels = [
            "group.{$groupId}",
        ];

        broadcast(new ChatMessageUpdated($channels, $payload))->toOthers();

        return response()->json(['data' => $message]);
    }

    public function deleteMessage(Request $request, Group $group, string $messageId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();

        $this->groups->assertAcceptedOrOwner(
            (string) $tenant->id,
            (string) $group->id,
            (string) $user->id,
            (string) $group->owner_id
        );

        $tenantId = (string) $tenant->id;
        $groupId = (string) $group->id;
        $userId = (string) $user->id;

        $this->messages->deleteMessage(
            tenantId: $tenantId,
            groupId: $groupId,
            messageId: $messageId,
            userId: $userId
        );

        $payload = [
            'type' => 'group',
            'tenant_id' => $tenantId,
            'group_id' => $groupId,
            'message_id' => $messageId,
        ];

        $channels = [
            "group.{$groupId}",
        ];

        broadcast(new ChatMessageDeleted($channels, $payload))->toOthers();

        return response()->json(['message' => 'Mensaje eliminado.']);
    }

    public function hide(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $this->groups->hideGroupForUser(
            tenantId: (string) $tenant->id,
            groupId: (string) $group->id,
            userId: (string) $request->user()->id
        );

        return response()->json([
            'message' => 'Grupo ocultado correctamente.'
        ]);
    }

    public function unhide(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $this->groups->unhideGroupForUser(
            tenantId: (string) $tenant->id,
            groupId: (string) $group->id,
            userId: (string) $request->user()->id
        );

        return response()->json([
            'message' => 'Grupo restaurado correctamente.'
        ]);
    }
    public function hidden(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400);

        return response()->json([
            'data' => $this->groups->listHiddenGroups(
                tenantId: (string) $tenant->id,
                userId: (string) $request->user()->id
            )
        ]);
    }
}

