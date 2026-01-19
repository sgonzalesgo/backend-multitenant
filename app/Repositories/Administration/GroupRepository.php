<?php

namespace App\Repositories\Administration;

// global import
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// local import
use App\Models\Administration\User;
use App\Models\Administration\Group;

class GroupRepository
{
    /**
     * Admin ve todos los grupos del tenant.
     * No-admin ve solo: owner o miembro accepted.
     * Además devuelve computed fields: is_owner (bool), is_member (bool), members_count (int).
     */
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
            // Postgres: case-insensitive search
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
            // Postgres boolean real
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

    public function createGroup(string $tenantId, string $ownerId, string $name): Group
    {
        return DB::transaction(function () use ($tenantId, $ownerId, $name) {
            /** @var Group $group */
            $group = Group::create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'owner_id' => $ownerId,
            ]);

            // Owner entra aceptado
            DB::table('group_members')->insert([
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
        string $groupId,
        string $groupOwnerId,
        string $actorId,
        string $targetUserId
    ): void {
        // Solo owner (por ahora)
        abort_unless($groupOwnerId === $actorId, 403, 'Solo el owner puede invitar.');

        // Evitar auto-invite
        abort_if($actorId === $targetUserId, 422, 'No puedes invitarte a ti mismo.');

        // Upsert membresía (si existe, vuelve a invited)
        DB::table('group_members')->updateOrInsert(
            ['group_id' => $groupId, 'user_id' => $targetUserId],
            [
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

    public function acceptInvitation(string $groupId, string $userId): void
    {
        $row = DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        abort_if(! $row, 404, 'No tienes invitación a este grupo.');
        abort_if($row->status !== 'invited', 422, 'No hay invitación pendiente.');

        DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->update([
                'status' => 'accepted',
                'joined_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function rejectInvitation(string $groupId, string $userId): void
    {
        $row = DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        abort_if(! $row, 404, 'No tienes invitación a este grupo.');
        abort_if($row->status !== 'invited', 422, 'No hay invitación pendiente.');

        // Opción limpia: borrar registro
        DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Devuelve miembros accepted.
     * Solo permitido si requester es accepted o es owner.
     */
    public function acceptedMembers(string $groupId, string $requesterId, string $groupOwnerId): array
    {
        $isMember = DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $requesterId)
            ->where('status', 'accepted')
            ->exists();

        abort_unless($isMember || $groupOwnerId === $requesterId, 403);

        return DB::table('group_members')
            ->join('users', 'users.id', '=', 'group_members.user_id')
            ->where('group_members.group_id', $groupId)
            ->where('group_members.status', 'accepted')
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->get()
            ->all();
    }

    /**
     * Rol admin scoping por tenant (Spatie Permission + Teams).
     */
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

    public function assertAcceptedOrOwner(string $groupId, string $requesterId, string $groupOwnerId): void
    {
        $isMember = DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $requesterId)
            ->where('status', 'accepted')
            ->exists();

        abort_unless($isMember || $groupOwnerId === $requesterId, 403);
    }
}
