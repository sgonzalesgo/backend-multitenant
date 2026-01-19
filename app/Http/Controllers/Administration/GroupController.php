<?php

namespace App\Http\Controllers\Administration;

// global imports
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// local imports
use App\Events\Groups\MessageSent;
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


    public function index(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $user = $request->user();

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
        $targetId = (string) $data['user_id'];

        $this->groups->inviteUserToGroup(
            groupId: (string) $group->id,
            groupOwnerId: (string) $group->owner_id,
            actorId: (string) $actor->id,
            targetUserId: $targetId
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

        // Autoriza (accepted o owner) y obtiene miembros accepted
        $members = $this->groups->acceptedMembers(
            groupId: (string) $group->id,
            requesterId: (string) $user->id,
            groupOwnerId: (string) $group->owner_id
        );

        $data = collect($members)->map(function ($m) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'online' => $this->authRepo->isOnline((string) $m->id),
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
        $this->groups->assertAcceptedOrOwner((string) $group->id, (string) $user->id, (string) $group->owner_id);

        $perPage = (int) $request->query('per_page', 30);

        return response()->json(
            $this->messages->paginateMessages((string) $group->id, $perPage)
        );
    }

    public function sendMessage(Request $request, Group $group): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');
        abort_unless((string) $group->tenant_id === (string) $tenant->id, 404);

        $user = $request->user();
        $this->groups->assertAcceptedOrOwner((string) $group->id, (string) $user->id, (string) $group->owner_id);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $this->messages->createMessage((string) $group->id, (string) $user->id, $data['body']);

        // Emite a otros miembros conectados del grupo
        broadcast(new MessageSent((string) $group->id, (array) $message))->toOthers();

        return response()->json(['data' => $message], 201);
    }
}
