<?php
//
//namespace App\Repositories\Administration;
//
//use App\Models\Administration\Chat\Group;
//use App\Models\Administration\User;
//use Illuminate\Contracts\Pagination\LengthAwarePaginator;
//use Illuminate\Support\Facades\DB;
//
//class GroupRepository
//{
//    public function paginateVisibleGroups(string $tenantId, string $userId, int $perPage = 15, string $q = ''): LengthAwarePaginator {
//        $isAdmin = $this->isAdminForTenant($userId, $tenantId);
//
//        $query = DB::table('groups as g')
//            ->where('g.tenant_id', $tenantId);
//
//        if (! $isAdmin) {
//            $query->where(function ($qb) use ($userId) {
//                $qb->where('g.owner_id', $userId)
//                    ->orWhereExists(function ($sub) use ($userId) {
//                        $sub->from('group_members as gm')
//                            ->whereColumn('gm.group_id', 'g.id')
//                            ->where('gm.user_id', $userId)
//                            ->where('gm.status', 'accepted');
//                    });
//            });
//        }
//
//        if ($q !== '') {
//            $query->where('g.name', 'ilike', "%{$q}%");
//        }
//
//        $query
//            ->select([
//                'g.id',
//                'g.name',
//                'g.owner_id',
//                'g.tenant_id',
//                'g.created_at',
//            ])
//            ->selectRaw('(g.owner_id = ?) AS is_owner', [$userId])
//            ->selectRaw(
//                "EXISTS(
//                    SELECT 1
//                    FROM group_members gm
//                    WHERE gm.group_id = g.id
//                      AND gm.user_id = ?
//                      AND gm.status = 'accepted'
//                ) AS is_member",
//                [$userId]
//            )
//            ->selectRaw(
//                "(SELECT COUNT(*)
//                  FROM group_members gm2
//                  WHERE gm2.group_id = g.id
//                    AND gm2.status = 'accepted'
//                ) AS members_count"
//            );
//
//        return $query
//            ->orderByDesc('g.created_at')
//            ->paginate($perPage);
//    }
//
//    /**
//     * ✅ Sidebar Slack-like: último mensaje + unread_count + last_read_at
//     * OJO: group_messages usa user_id (no sender_id)
//     */
//    public function listMyGroupsWithUnread(string $tenantId, string $userId): array
//    {
//        $lastMsgSub = DB::table('group_messages')
//            ->selectRaw('group_id, MAX(created_at) as last_message_at')
//            ->where('tenant_id', $tenantId)
//            ->groupBy('group_id');
//
//        $readsSub = DB::table('group_conversation_reads')
//            ->select(['group_id', 'last_read_at'])
//            ->where('tenant_id', $tenantId)
//            ->where('user_id', $userId);
//
//        $rows = DB::table('groups as g')
//            ->leftJoin('group_members as gm', function ($join) use ($tenantId, $userId) {
//                $join->on('gm.group_id', '=', 'g.id')
//                    ->where('gm.tenant_id', '=', $tenantId)
//                    ->where('gm.user_id', '=', $userId)
//                    ->where('gm.status', '=', 'accepted');
//            })
//            ->where('g.tenant_id', $tenantId)
//            ->where(function ($q) use ($userId) {
//                $q->where('g.owner_id', $userId)
//                    ->orWhereNotNull('gm.user_id');
//            })
//            ->leftJoinSub($lastMsgSub, 'lm', fn ($j) => $j->on('lm.group_id', '=', 'g.id'))
//            ->leftJoin('group_messages as msg', function ($join) use ($tenantId) {
//                $join->on('msg.group_id', '=', 'g.id')
//                    ->on('msg.created_at', '=', 'lm.last_message_at')
//                    ->where('msg.tenant_id', '=', $tenantId);
//            })
//            ->leftJoinSub($readsSub, 'r', fn ($j) => $j->on('r.group_id', '=', 'g.id'))
//            ->select([
//                'g.id',
//                'g.tenant_id',
//                'g.name',
//                'g.owner_id',
//                'g.created_at',
//
//                'msg.id as last_message_id',
//                'msg.body as last_message_body',
//                'msg.user_id as last_message_user_id',
//                'msg.created_at as last_message_created_at',
//
//                'r.last_read_at',
//            ])
//            ->selectRaw("
//                (
//                    SELECT COUNT(*)
//                    FROM group_messages gm2
//                    WHERE gm2.tenant_id = ?
//                      AND gm2.group_id = g.id
//                      AND gm2.user_id <> ?
//                      AND gm2.created_at > COALESCE(r.last_read_at, TIMESTAMP '1970-01-01 00:00:00')
//                ) as unread_count
//            ", [$tenantId, $userId])
//            ->orderByRaw('COALESCE(lm.last_message_at, g.created_at) DESC')
//            ->get();
//
//        return $rows->toArray();
//    }
//
//    public function createGroup(string $tenantId, string $ownerId, string $name): Group
//    {
//        return DB::transaction(function () use ($tenantId, $ownerId, $name) {
//            /** @var Group $group */
//            $group = Group::create([
//                'tenant_id' => $tenantId,
//                'name' => $name,
//                'owner_id' => $ownerId,
//            ]);
//
//            // ✅ Owner entra aceptado (con tenant_id)
//            DB::table('group_members')->insert([
//                'tenant_id' => $tenantId,
//                'group_id' => (string) $group->id,
//                'user_id' => $ownerId,
//                'status' => 'accepted',
//                'invited_by' => $ownerId,
//                'joined_at' => now(),
//                'created_at' => now(),
//                'updated_at' => now(),
//            ]);
//
//            return $group;
//        });
//    }
//
//    public function inviteUserToGroup(string $tenantId, string $groupId, string $groupOwnerId, string $actorId, string $targetUserId): void {
//        abort_unless($groupOwnerId === $actorId, 403, 'Solo el owner puede invitar.');
//        abort_if($actorId === $targetUserId, 422, 'No puedes invitarte a ti mismo.');
//
//        // ✅ Upsert membresía (con tenant_id)
//        DB::table('group_members')->updateOrInsert(
//            ['tenant_id' => $tenantId, 'group_id' => $groupId, 'user_id' => $targetUserId],
//            [
//                'tenant_id' => $tenantId,
//                'status' => 'invited',
//                'invited_by' => $actorId,
//                'joined_at' => null,
//                'updated_at' => now(),
//                'created_at' => now(),
//            ]
//        );
//    }
//
//    public function myInvitations(string $tenantId, string $userId): array
//    {
//        return DB::table('group_members as gm')
//            ->join('groups as g', 'g.id', '=', 'gm.group_id')
//            ->where('gm.tenant_id', $tenantId)
//            ->where('gm.user_id', $userId)
//            ->where('gm.status', 'invited')
//            ->where('g.tenant_id', $tenantId)
//            ->select([
//                'g.id',
//                'g.name',
//                'g.owner_id',
//                'g.created_at',
//                'gm.invited_by',
//                'gm.created_at as invited_at',
//            ])
//            ->orderByDesc('gm.created_at')
//            ->get()
//            ->all();
//    }
//
//    public function acceptInvitation(string $tenantId, string $groupId, string $userId): void
//    {
//        $row = DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $userId)
//            ->first();
//
//        abort_if(! $row, 404, 'No tienes invitación a este grupo.');
//        abort_if($row->status !== 'invited', 422, 'No hay invitación pendiente.');
//
//        DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $userId)
//            ->update([
//                'status' => 'accepted',
//                'joined_at' => now(),
//                'updated_at' => now(),
//            ]);
//    }
//
//    public function rejectInvitation(string $tenantId, string $groupId, string $userId): void
//    {
//        $row = DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $userId)
//            ->first();
//
//        abort_if(! $row, 404, 'No tienes invitación a este grupo.');
//        abort_if($row->status !== 'invited', 422, 'No hay invitación pendiente.');
//
//        DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $userId)
//            ->delete();
//    }
//
//    public function acceptedMembers(string $tenantId, string $groupId, string $requesterId, string $groupOwnerId): array
//    {
//        $isMember = DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $requesterId)
//            ->where('status', 'accepted')
//            ->exists();
//
//        abort_unless($isMember || $groupOwnerId === $requesterId, 403);
//
//        return DB::table('group_members')
//            ->join('users', 'users.id', '=', 'group_members.user_id')
//            ->where('group_members.tenant_id', $tenantId)
//            ->where('group_members.group_id', $groupId)
//            ->where('group_members.status', 'accepted')
//            ->select('users.id', 'users.name', 'users.email')
//            ->orderBy('users.name')
//            ->get()
//            ->all();
//    }
//
//    protected function isAdminForTenant(string $userId, string $tenantId): bool
//    {
//        $teamFk = config('permission.team_foreign_key', 'tenant_id');
//
//        return DB::table('model_has_roles as mhr')
//            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
//            ->where('mhr.model_type', User::class)
//            ->where('mhr.model_id', $userId)
//            ->where("mhr.{$teamFk}", $tenantId)
//            ->where('r.name', 'admin')
//            ->exists();
//    }
//
//    public function assertAcceptedOrOwner(string $tenantId, string $groupId, string $requesterId, string $groupOwnerId): void
//    {
//        $isMember = DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $requesterId)
//            ->where('status', 'accepted')
//            ->exists();
//
//        abort_unless($isMember || $groupOwnerId === $requesterId, 403);
//    }
//
//    public function markReadAt(string $tenantId, string $groupId, string $userId, ?string $readAtIso = null): string
//    {
//        $readAt = $readAtIso ? now()->parse($readAtIso) : now();
//        $now = now();
//
//        DB::table('group_conversation_reads')->upsert(
//            [
//                [
//                    'tenant_id' => $tenantId,
//                    'group_id' => $groupId,
//                    'user_id' => $userId,
//                    'last_read_at' => $readAt,
//                    'created_at' => $now,
//                    'updated_at' => $now,
//                ]
//            ],
//            ['tenant_id', 'group_id', 'user_id'],
//            ['last_read_at', 'updated_at']
//        );
//
//        return $readAt->toIso8601String();
//    }
//
//    public function updateGroup(string $tenantId, string $groupId, string $ownerId, string $name): array
//    {
//        $exists = DB::table('groups')
//            ->where('id', $groupId)
//            ->where('tenant_id', $tenantId)
//            ->where('owner_id', $ownerId)
//            ->exists();
//
//        abort_unless($exists, 404);
//
//        DB::table('groups')
//            ->where('id', $groupId)
//            ->where('tenant_id', $tenantId)
//            ->where('owner_id', $ownerId)
//            ->update([
//                'name' => trim($name),
//                'updated_at' => now(),
//            ]);
//
//        return (array) DB::table('groups')
//            ->where('id', $groupId)
//            ->where('tenant_id', $tenantId)
//            ->first();
//    }
//
//    public function deleteGroup(string $tenantId, string $groupId, string $ownerId): void
//    {
//        $exists = DB::table('groups')
//            ->where('id', $groupId)
//            ->where('tenant_id', $tenantId)
//            ->where('owner_id', $ownerId)
//            ->exists();
//
//        abort_unless($exists, 404);
//
//        DB::transaction(function () use ($tenantId, $groupId, $ownerId) {
//            DB::table('group_members')
//                ->where('tenant_id', $tenantId)
//                ->where('group_id', $groupId)
//                ->delete();
//
//            DB::table('group_messages')
//                ->where('tenant_id', $tenantId)
//                ->where('group_id', $groupId)
//                ->delete();
//
//            DB::table('group_conversation_reads')
//                ->where('tenant_id', $tenantId)
//                ->where('group_id', $groupId)
//                ->delete();
//
//            DB::table('groups')
//                ->where('id', $groupId)
//                ->where('tenant_id', $tenantId)
//                ->where('owner_id', $ownerId)
//                ->delete();
//        });
//    }
//
//    public function getGroupUserIdsForRealtime(string $tenantId, string $groupId, string $ownerId): array
//    {
//        $memberIds = DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->pluck('user_id')
//            ->map(fn ($id) => (string) $id)
//            ->all();
//
//        return array_values(array_unique([
//            (string) $ownerId,
//            ...$memberIds,
//        ]));
//    }
//
//    public function leaveGroup(string $tenantId, string $groupId, string $userId, string $groupOwnerId): void
//    {
//        abort_if((string) $userId === (string) $groupOwnerId, 422, 'El owner no puede salir del grupo.');
//
//        $deleted = DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $userId)
//            ->where('status', 'accepted')
//            ->delete();
//
//        abort_unless($deleted > 0, 404, 'No eres miembro activo de este grupo.');
//
//        DB::table('group_conversation_reads')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $userId)
//            ->delete();
//    }
//
//    public function removeMember(string $tenantId, string $groupId, string $ownerId, string $targetUserId, string $groupOwnerId): void
//    {
//        abort_unless((string) $ownerId === (string) $groupOwnerId, 403, 'Solo el owner puede expulsar miembros.');
//        abort_if((string) $targetUserId === (string) $groupOwnerId, 422, 'No puedes expulsarte a ti mismo como owner.');
//
//        $deleted = DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $targetUserId)
//            ->where('status', 'accepted')
//            ->delete();
//
//        abort_unless($deleted > 0, 404, 'El usuario no es miembro activo de este grupo.');
//
//        DB::table('group_conversation_reads')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $targetUserId)
//            ->delete();
//    }
//
//    public function hideGroupForUser(string $tenantId, string $groupId, string $userId): void
//    {
//        $group = DB::table('groups')
//            ->where('tenant_id', $tenantId)
//            ->where('id', $groupId)
//            ->first();
//
//        abort_unless($group, 404, 'Grupo no encontrado.');
//
//        if ((string) $group->owner_id === $userId) {
//            DB::table('group_members')
//                ->where('tenant_id', $tenantId)
//                ->where('group_id', $groupId)
//                ->where('user_id', $userId)
//                ->update([
//                    'hidden_at' => now(),
//                    'updated_at' => now(),
//                ]);
//
//            return;
//        }
//
//        $membership = DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $userId)
//            ->where('status', 'accepted')
//            ->first();
//
//        abort_unless($membership, 403, 'No perteneces a este grupo.');
//
//        DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $userId)
//            ->update([
//                'hidden_at' => now(),
//                'updated_at' => now(),
//            ]);
//    }
//
//    public function unhideGroupForUser(string $tenantId, string $groupId, string $userId): void
//    {
//        $group = DB::table('groups')
//            ->where('tenant_id', $tenantId)
//            ->where('id', $groupId)
//            ->first();
//
//        abort_unless($group, 404, 'Grupo no encontrado.');
//
//        if ((string) $group->owner_id === $userId) {
//            DB::table('group_members')
//                ->where('tenant_id', $tenantId)
//                ->where('group_id', $groupId)
//                ->where('user_id', $userId)
//                ->update([
//                    'hidden_at' => null,
//                    'updated_at' => now(),
//                ]);
//
//            return;
//        }
//
//        $membership = DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $userId)
//            ->where('status', 'accepted')
//            ->first();
//
//        abort_unless($membership, 403, 'No perteneces a este grupo.');
//
//        DB::table('group_members')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('user_id', $userId)
//            ->update([
//                'hidden_at' => null,
//                'updated_at' => now(),
//            ]);
//    }
//}

//----------------------------------------------------------------------------------


namespace App\Repositories\Administration;

use App\Models\Administration\Chat\Group;
use App\Models\Administration\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GroupRepository
{
    public function paginateVisibleGroups(
        string $tenantId,
        string $userId,
        int    $perPage = 15,
        string $q = ''
    ): LengthAwarePaginator
    {
        $isAdmin = $this->isAdminForTenant($userId, $tenantId);

        $query = DB::table('groups as g')
            ->where('g.tenant_id', $tenantId);

        if (!$isAdmin) {
            $query->where(function ($qb) use ($userId) {
                $qb->where('g.owner_id', $userId)
                    ->orWhereExists(function ($sub) use ($userId) {
                        $sub->from('group_members as gm')
                            ->whereColumn('gm.group_id', 'g.id')
                            ->where('gm.user_id', $userId)
                            ->where('gm.status', 'accepted');
                    });
            });
        }

        if ($q !== '') {
            $query->where('g.name', 'ilike', "%{$q}%");
        }

        $query
            ->select([
                'g.id',
                'g.name',
                'g.owner_id',
                'g.tenant_id',
                'g.created_at',
            ])
            ->selectRaw('(g.owner_id = ?) AS is_owner', [$userId])
            ->selectRaw(
                "EXISTS(
                    SELECT 1
                    FROM group_members gm
                    WHERE gm.group_id = g.id
                      AND gm.user_id = ?
                      AND gm.status = 'accepted'
                ) AS is_member",
                [$userId]
            )
            ->selectRaw(
                "(SELECT COUNT(*)
                  FROM group_members gm2
                  WHERE gm2.group_id = g.id
                    AND gm2.status = 'accepted'
                ) AS members_count"
            );

        return $query
            ->orderByDesc('g.created_at')
            ->paginate($perPage);
    }

    /**
     * Sidebar: último mensaje + unread_count + last_read_at
     * Solo grupos visibles para el usuario actual (hidden_at IS NULL en group_members).
     */
    public function listMyGroupsWithUnread(string $tenantId, string $userId): array
    {
        $lastMsgSub = DB::table('group_messages')
            ->selectRaw('group_id, MAX(created_at) as last_message_at')
            ->where('tenant_id', $tenantId)
            ->groupBy('group_id');

        $readsSub = DB::table('group_conversation_reads')
            ->select(['group_id', 'last_read_at'])
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId);

        $rows = DB::table('groups as g')
            ->leftJoin('group_members as gm', function ($join) use ($tenantId, $userId) {
                $join->on('gm.group_id', '=', 'g.id')
                    ->where('gm.tenant_id', '=', $tenantId)
                    ->where('gm.user_id', '=', $userId)
                    ->where('gm.status', '=', 'accepted');
            })
            ->where('g.tenant_id', $tenantId)
            ->whereExists(function ($sub) use ($tenantId, $userId) {
                $sub->from('group_members as gm_visible')
                    ->whereColumn('gm_visible.group_id', 'g.id')
                    ->where('gm_visible.tenant_id', $tenantId)
                    ->where('gm_visible.user_id', $userId)
                    ->where('gm_visible.status', 'accepted')
                    ->whereNull('gm_visible.hidden_at');
            })
            ->leftJoinSub($lastMsgSub, 'lm', fn($j) => $j->on('lm.group_id', '=', 'g.id'))
            ->leftJoin('group_messages as msg', function ($join) use ($tenantId) {
                $join->on('msg.group_id', '=', 'g.id')
                    ->on('msg.created_at', '=', 'lm.last_message_at')
                    ->where('msg.tenant_id', '=', $tenantId);
            })
            ->leftJoinSub($readsSub, 'r', fn($j) => $j->on('r.group_id', '=', 'g.id'))
            ->select([
                'g.id',
                'g.tenant_id',
                'g.name',
                'g.owner_id',
                'g.created_at',

                'msg.id as last_message_id',
                'msg.body as last_message_body',
                'msg.user_id as last_message_user_id',
                'msg.created_at as last_message_created_at',

                'r.last_read_at',
            ])
            ->selectRaw(
                "
                (
                    SELECT COUNT(*)
                    FROM group_messages gm2
                    WHERE gm2.tenant_id = ?
                      AND gm2.group_id = g.id
                      AND gm2.user_id <> ?
                      AND gm2.created_at > COALESCE(r.last_read_at, TIMESTAMP '1970-01-01 00:00:00')
                ) as unread_count
                ",
                [$tenantId, $userId]
            )
            ->orderByRaw('COALESCE(lm.last_message_at, g.created_at) DESC')
            ->get();

        return $rows->toArray();
    }

    public function createGroup(string $tenantId, string $ownerId, string $name): Group
    {
        return DB::transaction(function () use ($tenantId, $ownerId, $name) {
            /** @var Group $group */
            $group = Group::create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'owner_id' => $ownerId,
            ]);

            DB::table('group_members')->insert([
                'tenant_id' => $tenantId,
                'group_id' => (string)$group->id,
                'user_id' => $ownerId,
                'status' => 'accepted',
                'invited_by' => $ownerId,
                'joined_at' => now(),
                'hidden_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $group;
        });
    }

    public function inviteUserToGroup(
        string $tenantId,
        string $groupId,
        string $groupOwnerId,
        string $actorId,
        string $targetUserId
    ): void
    {
        abort_unless($groupOwnerId === $actorId, 403, 'Solo el owner puede invitar.');
        abort_if($actorId === $targetUserId, 422, 'No puedes invitarte a ti mismo.');

        DB::table('group_members')->updateOrInsert(
            ['tenant_id' => $tenantId, 'group_id' => $groupId, 'user_id' => $targetUserId],
            [
                'tenant_id' => $tenantId,
                'status' => 'invited',
                'invited_by' => $actorId,
                'joined_at' => null,
                'hidden_at' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function myInvitations(string $tenantId, string $userId): array
    {
        return DB::table('group_members as gm')
            ->join('groups as g', 'g.id', '=', 'gm.group_id')
            ->where('gm.tenant_id', $tenantId)
            ->where('gm.user_id', $userId)
            ->where('gm.status', 'invited')
            ->where('g.tenant_id', $tenantId)
            ->select([
                'g.id',
                'g.name',
                'g.owner_id',
                'g.created_at',
                'gm.invited_by',
                'gm.created_at as invited_at',
            ])
            ->orderByDesc('gm.created_at')
            ->get()
            ->all();
    }

    public function acceptInvitation(string $tenantId, string $groupId, string $userId): void
    {
        $row = DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        abort_if(!$row, 404, 'No tienes invitación a este grupo.');
        abort_if($row->status !== 'invited', 422, 'No hay invitación pendiente.');

        DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->update([
                'status' => 'accepted',
                'joined_at' => now(),
                'hidden_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function rejectInvitation(string $tenantId, string $groupId, string $userId): void
    {
        $row = DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        abort_if(!$row, 404, 'No tienes invitación a este grupo.');
        abort_if($row->status !== 'invited', 422, 'No hay invitación pendiente.');

        DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function acceptedMembers(string $tenantId, string $groupId, string $requesterId, string $groupOwnerId): array
    {
        $isMember = DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $requesterId)
            ->where('status', 'accepted')
            ->exists();

        abort_unless($isMember || $groupOwnerId === $requesterId, 403);

        return DB::table('group_members')
            ->join('users', 'users.id', '=', 'group_members.user_id')
            ->where('group_members.tenant_id', $tenantId)
            ->where('group_members.group_id', $groupId)
            ->where('group_members.status', 'accepted')
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->get()
            ->all();
    }

    protected function isAdminForTenant(string $userId, string $tenantId): bool
    {
        $teamFk = config('permission.team_foreign_key', 'tenant_id');

        return DB::table('model_has_roles as mhr')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('mhr.model_type', User::class)
            ->where('mhr.model_id', $userId)
            ->where("mhr.{$teamFk}", $tenantId)
            ->where('r.name', 'admin')
            ->exists();
    }

    public function assertAcceptedOrOwner(string $tenantId, string $groupId, string $requesterId, string $groupOwnerId): void
    {
        $isMember = DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $requesterId)
            ->where('status', 'accepted')
            ->exists();

        abort_unless($isMember || $groupOwnerId === $requesterId, 403);
    }

    public function markReadAt(string $tenantId, string $groupId, string $userId, ?string $readAtIso = null): string
    {
        $readAt = $readAtIso ? now()->parse($readAtIso) : now();
        $now = now();

        DB::table('group_conversation_reads')->upsert(
            [
                [
                    'tenant_id' => $tenantId,
                    'group_id' => $groupId,
                    'user_id' => $userId,
                    'last_read_at' => $readAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            ],
            ['tenant_id', 'group_id', 'user_id'],
            ['last_read_at', 'updated_at']
        );

        return $readAt->toIso8601String();
    }

    public function updateGroup(string $tenantId, string $groupId, string $ownerId, string $name): array
    {
        $exists = DB::table('groups')
            ->where('id', $groupId)
            ->where('tenant_id', $tenantId)
            ->where('owner_id', $ownerId)
            ->exists();

        abort_unless($exists, 404);

        DB::table('groups')
            ->where('id', $groupId)
            ->where('tenant_id', $tenantId)
            ->where('owner_id', $ownerId)
            ->update([
                'name' => trim($name),
                'updated_at' => now(),
            ]);

        return (array)DB::table('groups')
            ->where('id', $groupId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function deleteGroup(string $tenantId, string $groupId, string $ownerId): void
    {
        $exists = DB::table('groups')
            ->where('id', $groupId)
            ->where('tenant_id', $tenantId)
            ->where('owner_id', $ownerId)
            ->exists();

        abort_unless($exists, 404);

        DB::transaction(function () use ($tenantId, $groupId, $ownerId) {
            DB::table('group_members')
                ->where('tenant_id', $tenantId)
                ->where('group_id', $groupId)
                ->delete();

            DB::table('group_messages')
                ->where('tenant_id', $tenantId)
                ->where('group_id', $groupId)
                ->delete();

            DB::table('group_conversation_reads')
                ->where('tenant_id', $tenantId)
                ->where('group_id', $groupId)
                ->delete();

            DB::table('groups')
                ->where('id', $groupId)
                ->where('tenant_id', $tenantId)
                ->where('owner_id', $ownerId)
                ->delete();
        });
    }

    public function getGroupUserIdsForRealtime(string $tenantId, string $groupId, string $ownerId): array
    {
        $memberIds = DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->pluck('user_id')
            ->map(fn($id) => (string)$id)
            ->all();

        return array_values(array_unique([
            (string)$ownerId,
            ...$memberIds,
        ]));
    }

    public function leaveGroup(string $tenantId, string $groupId, string $userId, string $groupOwnerId): void
    {
        abort_if((string)$userId === (string)$groupOwnerId, 422, 'El owner no puede salir del grupo.');

        $deleted = DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->where('status', 'accepted')
            ->delete();

        abort_unless($deleted > 0, 404, 'No eres miembro activo de este grupo.');

        DB::table('group_conversation_reads')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function removeMember(string $tenantId, string $groupId, string $ownerId, string $targetUserId, string $groupOwnerId): void
    {
        abort_unless((string)$ownerId === (string)$groupOwnerId, 403, 'Solo el owner puede expulsar miembros.');
        abort_if((string)$targetUserId === (string)$groupOwnerId, 422, 'No puedes expulsarte a ti mismo como owner.');

        $deleted = DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $targetUserId)
            ->where('status', 'accepted')
            ->delete();

        abort_unless($deleted > 0, 404, 'El usuario no es miembro activo de este grupo.');

        DB::table('group_conversation_reads')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $targetUserId)
            ->delete();
    }

    public function hideGroupForUser(string $tenantId, string $groupId, string $userId): void
    {
        $group = DB::table('groups')
            ->where('tenant_id', $tenantId)
            ->where('id', $groupId)
            ->first();

        abort_unless($group, 404, 'Grupo no encontrado.');

        $membership = DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->where('status', 'accepted')
            ->first();

        abort_unless($membership || (string)$group->owner_id === $userId, 403, 'No perteneces a este grupo.');

        DB::table('group_members')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'group_id' => $groupId,
                'user_id' => $userId,
            ],
            [
                'status' => 'accepted',
                'invited_by' => $membership->invited_by ?? $group->owner_id,
                'joined_at' => $membership->joined_at ?? now(),
                'hidden_at' => now(),
                'updated_at' => now(),
                'created_at' => $membership->created_at ?? now(),
            ]
        );
    }

    public function unhideGroupForUser(string $tenantId, string $groupId, string $userId): void
    {
        $group = DB::table('groups')
            ->where('tenant_id', $tenantId)
            ->where('id', $groupId)
            ->first();

        abort_unless($group, 404, 'Grupo no encontrado.');

        $membership = DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->where('status', 'accepted')
            ->first();

        abort_unless($membership || (string)$group->owner_id === $userId, 403, 'No perteneces a este grupo.');

        DB::table('group_members')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'group_id' => $groupId,
                'user_id' => $userId,
            ],
            [
                'status' => 'accepted',
                'invited_by' => $membership->invited_by ?? $group->owner_id,
                'joined_at' => $membership->joined_at ?? now(),
                'hidden_at' => null,
                'updated_at' => now(),
                'created_at' => $membership->created_at ?? now(),
            ]
        );
    }

    public function listHiddenGroups(string $tenantId, string $userId): array
    {
        return DB::table('groups as g')
            ->join('group_members as gm', function ($join) use ($tenantId, $userId) {
                $join->on('gm.group_id', '=', 'g.id')
                    ->where('gm.tenant_id', '=', $tenantId)
                    ->where('gm.user_id', '=', $userId)
                    ->whereNotNull('gm.hidden_at');
            })
            ->where('g.tenant_id', $tenantId)
            ->select([
                'g.id',
                'g.name',
                'gm.hidden_at'
            ])
            ->orderByDesc('gm.hidden_at')
            ->get()
            ->toArray();
    }
}
