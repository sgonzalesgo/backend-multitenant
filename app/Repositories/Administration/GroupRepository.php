<?php

namespace App\Repositories\Administration;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Administration\User;
use App\Models\Administration\Group;

class GroupRepository
{
    public function paginateVisibleGroups(
        string $tenantId,
        string $userId,
        int $perPage = 15,
        string $q = ''
    ): LengthAwarePaginator {
        $isAdmin = $this->isAdminForTenant($userId, $tenantId);

        $query = DB::table('groups as g')
            ->where('g.tenant_id', $tenantId);

        if (! $isAdmin) {
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
     * ✅ Sidebar Slack-like: último mensaje + unread_count + last_read_at
     * OJO: group_messages usa user_id (no sender_id)
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
            ->where(function ($q) use ($userId) {
                $q->where('g.owner_id', $userId)
                    ->orWhereNotNull('gm.user_id');
            })
            ->leftJoinSub($lastMsgSub, 'lm', fn ($j) => $j->on('lm.group_id', '=', 'g.id'))
            ->leftJoin('group_messages as msg', function ($join) use ($tenantId) {
                $join->on('msg.group_id', '=', 'g.id')
                    ->on('msg.created_at', '=', 'lm.last_message_at')
                    ->where('msg.tenant_id', '=', $tenantId);
            })
            ->leftJoinSub($readsSub, 'r', fn ($j) => $j->on('r.group_id', '=', 'g.id'))
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
            ->selectRaw("
                (
                    SELECT COUNT(*)
                    FROM group_messages gm2
                    WHERE gm2.tenant_id = ?
                      AND gm2.group_id = g.id
                      AND gm2.user_id <> ?
                      AND gm2.created_at > COALESCE(r.last_read_at, TIMESTAMP '1970-01-01 00:00:00')
                ) as unread_count
            ", [$tenantId, $userId])
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

            // ✅ Owner entra aceptado (con tenant_id)
            DB::table('group_members')->insert([
                'tenant_id' => $tenantId,
                'group_id' => (string) $group->id,
                'user_id' => $ownerId,
                'status' => 'accepted',
                'invited_by' => $ownerId,
                'joined_at' => now(),
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
    ): void {
        abort_unless($groupOwnerId === $actorId, 403, 'Solo el owner puede invitar.');
        abort_if($actorId === $targetUserId, 422, 'No puedes invitarte a ti mismo.');

        // ✅ Upsert membresía (con tenant_id)
        DB::table('group_members')->updateOrInsert(
            ['group_id' => $groupId, 'user_id' => $targetUserId],
            [
                'tenant_id' => $tenantId,
                'status' => 'invited',
                'invited_by' => $actorId,
                'joined_at' => null,
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

        abort_if(! $row, 404, 'No tienes invitación a este grupo.');
        abort_if($row->status !== 'invited', 422, 'No hay invitación pendiente.');

        DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->update([
                'status' => 'accepted',
                'joined_at' => now(),
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

        abort_if(! $row, 404, 'No tienes invitación a este grupo.');
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
}
