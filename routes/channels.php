<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use App\Models\Administration\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Aquí defines quién puede suscribirse a canales privados/presence.
| MUY IMPORTANTE en multi-tenant: siempre valida pertenencia al tenant.
|--------------------------------------------------------------------------
*/

/**
 * Helper: valida si un usuario pertenece a un tenant (colegio).
 * En tu caso, lo estás modelando con Spatie Teams: model_has_roles + tenant_id.
 *
 * Si mañana cambias el mecanismo de pertenencia (ej: tenant_user),
 * solo actualizas esta función.
 */
function userBelongsToTenant(string $userId, string $tenantId): bool
{
    $teamFk = config('permission.team_foreign_key', 'tenant_id');
    $table = config('permission.table_names.model_has_roles', 'model_has_roles');

    return DB::table($table)
        ->where('model_type', User::class)
        ->where('model_id', $userId)
        ->where($teamFk, $tenantId)
        ->exists();
}

/**
 * PRESENCE CHANNEL POR TENANT (COLEGIO)
 *
 * Canal: presence.tenant.{tenantId}
 * Objetivo: mostrar lista de usuarios conectados en ese colegio/tenant.
 *
 * Reglas:
 * - SOLO usuarios que pertenezcan a ese tenant pueden unirse al canal.
 * - El payload retornado aquí es lo que los demás verán en "here/joining/leaving".
 */
Broadcast::channel('presence.tenant.{tenantId}', function ($user, $tenantId) {
    $userId = (string) $user->id;
    $tenantId = (string) $tenantId;

    if (! userBelongsToTenant($userId, $tenantId)) {
        return false;
    }

    // Payload visible en presence
    return [
        'id' => $userId,
        'name' => $user->name,
        // agrega más campos si lo necesitas (ej: avatar)
        // 'avatar' => $user->avatar_url ?? null,
    ];
});

/**
 * PRIVATE CHANNEL PARA CHAT DE GRUPO
 *
 * Canal: group.{groupId}
 * Objetivo: recibir:
 * - eventos de mensajes del grupo (MessageSent)
 * - eventos de member.online / member.offline
 *
 * Reglas:
 * - El grupo debe existir.
 * - El usuario debe pertenecer al tenant del grupo.
 * - El usuario debe ser owner DEL grupo o miembro accepted.
 *
 * Nota: esto previene que un usuario de otro colegio vea un grupo ajeno,
 * aunque conozca el UUID del grupo.
 */
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    $userId = (string) $user->id;
    $groupId = (string) $groupId;

    $group = DB::table('groups')
        ->select(['id', 'tenant_id', 'owner_id'])
        ->where('id', $groupId)
        ->first();

    if (! $group) {
        return false;
    }

    $tenantId = (string) $group->tenant_id;

    // 1) Aislamiento por tenant
    if (! userBelongsToTenant($userId, $tenantId)) {
        return false;
    }

    // 2) Owner siempre permitido
    if ((string) $group->owner_id === $userId) {
        return true;
    }

    // 3) Si no es owner, debe ser miembro accepted
    return DB::table('group_members')
        ->where('group_id', $groupId)
        ->where('user_id', $userId)
        ->where('status', 'accepted')
        ->exists();
});

/**
 * (OPCIONAL) PRIVATE CHANNEL PARA CHAT DIRECTO (DM 1-a-1)
 *
 * Canal: dm.{conversationId}
 * Objetivo: chat con un usuario en particular, sin grupo.
 *
 * Requiere que exista una tabla como:
 * - direct_conversations: id, tenant_id, user_one_id, user_two_id
 *
 * Si aún NO has creado DM, puedes dejar esto comentado.
 */
Broadcast::channel('dm.{conversationId}', function ($user, $conversationId) {
    $userId = (string) $user->id;
    $conversationId = (string) $conversationId;

    $conv = DB::table('direct_conversations')
        ->select(['id', 'tenant_id', 'user_one_id', 'user_two_id'])
        ->where('id', $conversationId)
        ->first();

    if (! $conv) return false;

    // Aislamiento por tenant (igual que tu helper actual)
    $tenantId = (string) $conv->tenant_id;

    $teamFk = config('permission.team_foreign_key', 'tenant_id');
    $table  = config('permission.table_names.model_has_roles', 'model_has_roles');

    $belongsToTenant = DB::table($table)
        ->where('model_type', \App\Models\Administration\User::class)
        ->where('model_id', $userId)
        ->where($teamFk, $tenantId)
        ->exists();

    if (! $belongsToTenant) return false;

    // Solo participantes
    return $userId === (string) $conv->user_one_id || $userId === (string) $conv->user_two_id;
});

/**
 * INBOX POR USUARIO Y TENANT
 * Canal: inbox.tenant.{tenantId}.user.{userId}
 * Sirve para:
 * - actualizar sidebar (último mensaje + mover arriba)
 * - incrementar unread_count sin recargar
 * - notificaciones realtime sin suscribirte a 100 conversaciones
 */
Broadcast::channel('inbox.tenant.{tenantId}.user.{userId}', function ($user, $tenantId, $userId) {
    $authUserId = (string) $user->id;
    $tenantId = (string) $tenantId;
    $userId = (string) $userId;

    // Solo el mismo usuario puede entrar a su inbox
    if ($authUserId !== $userId) {
        return false;
    }

    // Validar pertenencia al tenant (Spatie teams / model_has_roles)
    $teamFk = config('permission.team_foreign_key', 'tenant_id');
    $table  = config('permission.table_names.model_has_roles', 'model_has_roles');

    return DB::table($table)
        ->where('model_type', \App\Models\Administration\User::class)
        ->where('model_id', $authUserId)
        ->where($teamFk, $tenantId)
        ->exists();
});
