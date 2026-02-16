<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Administration\Group;
use App\Models\Administration\Tenant;
use App\Repositories\Administration\AuthRepository;
use App\Repositories\Administration\GroupRepository;
use App\Repositories\Administration\GroupMessageRepository;

class GroupController extends Controller
{
    public function __construct(
        private readonly AuthRepository $authRepo,
        private readonly GroupRepository $groups,
        private readonly GroupMessageRepository $messages
    ) {}

    /**
     * ✅ Lista Slack-like (sidebar): último mensaje + unread_count
     * Si quieres mantener paginado simple, puedes seguir usando paginateVisibleGroups().
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $user = $request->user();

        // Si viene ?sidebar=1 → devolvemos con unread_count
        $sidebar = (bool) $request->query('sidebar', false);

        if ($sidebar) {
            $rows = $this->groups->listMyGroupsWithUnread((string) $tenant->id, (string) $user->id);
            return response()->json(['data' => $rows]);
        }

        // Default: tu paginado existente
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

        $this->groups->inviteUserToGroup(
            tenantId: (string) $tenant->id,
            groupId: (string) $group->id,
            groupOwnerId: (string) $group->owner_id,
            actorId: (string) $actor->id,
            targetUserId: (string) $data['user_id']
        );

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

        $this->groups->acceptInvitation(
            tenantId: (string) $tenant->id,
            groupId: (string) $group->id,
            userId: (string) $user->id
        );

        return response()->json(['message' => 'Invitación aceptada.']);
    }

    public function reject(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();

        $this->groups->rejectInvitation(
            tenantId: (string) $tenant->id,
            groupId: (string) $group->id,
            userId: (string) $user->id
        );

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
                // si tu authRepo requiere tenantId, cámbialo aquí
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
        $this->groups->assertAcceptedOrOwner((string) $tenant->id, (string) $group->id, (string) $user->id, (string) $group->owner_id);

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
        $this->groups->assertAcceptedOrOwner((string) $tenant->id, (string) $group->id, (string) $user->id, (string) $group->owner_id);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $this->messages->createMessage(
            tenantId: (string) $tenant->id,
            groupId: (string) $group->id,
            userId: (string) $user->id,
            body: $data['body']
        );

        return response()->json(['data' => $message], 201);
    }
}
