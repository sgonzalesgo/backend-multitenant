<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use App\Models\Administration\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Aquí defines quién puede suscribirse a canales privados/presence.
| IMPORTANTE en multi-tenant: siempre valida pertenencia al tenant.
|--------------------------------------------------------------------------
*/

if (! function_exists('userBelongsToTenant')) {
    /**
     * Helper: valida si un usuario pertenece a un tenant (colegio).
     * En tu caso, lo estás modelando con Spatie Teams: model_has_roles + tenant_id.
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
}

/**
 * PRESENCE CHANNEL POR TENANT
 *
 * Frontend:
 *   echo.join(`tenant.${tenantId}`)
 *
 * Request real hacia broadcasting/auth:
 *   channel_name = presence-tenant.{tenantId}
 */
use Illuminate\Support\Facades\Log;

Broadcast::channel('tenant.{tenantId}', function ($user, $tenantId) {

    $userId = (string) $user->id;
    $tenantId = (string) $tenantId;

    if (! userBelongsToTenant($userId, $tenantId)) {
        return false;
    }

    return [
        'id' => $userId,
        'name' => $user->name,
    ];
});

/**
 * PRIVATE CHANNEL PARA CHAT DE GRUPO
 *
 * Frontend:
 *   echo.private(`group.${groupId}`)
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

    if (! userBelongsToTenant($userId, $tenantId)) {
        return false;
    }

    if ((string) $group->owner_id === $userId) {
        return true;
    }

    return DB::table('group_members')
        ->where('group_id', $groupId)
        ->where('user_id', $userId)
        ->where('status', 'accepted')
        ->exists();
});

/**
 * PRIVATE CHANNEL PARA CHAT DIRECTO (DM 1-a-1)
 *
 * Frontend:
 *   echo.private(`dm.${conversationId}`)
 *
 * La conversación vive en la tabla direct_conversations
 * con user_one_id / user_two_id.
 */
Broadcast::channel('dm.{conversationId}', function ($user, $conversationId) {
    $userId = (string) $user->id;
    $conversationId = (string) $conversationId;

    $conversation = DB::table('direct_conversations')
        ->select(['id', 'tenant_id', 'user_one_id', 'user_two_id'])
        ->where('id', $conversationId)
        ->first();

    if (! $conversation) {
        return false;
    }

    $tenantId = (string) $conversation->tenant_id;

    if (! userBelongsToTenant($userId, $tenantId)) {
        return false;
    }

    return $userId === (string) $conversation->user_one_id
        || $userId === (string) $conversation->user_two_id;
});

/**
 * PRIVATE CHANNEL PARA INBOX POR USUARIO DENTRO DE UN TENANT
 *
 * Frontend:
 *   echo.private(`inbox.tenant.${tenantId}.user.${userId}`)
 */
Broadcast::channel('inbox.tenant.{tenantId}.user.{userId}', function ($user, $tenantId, $userId) {
    $authUserId = (string) $user->id;
    $tenantId = (string) $tenantId;
    $userId = (string) $userId;

    if ($authUserId !== $userId) {
        return false;
    }

    return userBelongsToTenant($authUserId, $tenantId);
});

// notifications.user.{userId}
Broadcast::channel('notifications.user.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

//----------------------------- calendar module --------------------------------------------
Broadcast::channel('tenant.{tenantId}.calendar', function (User $user, string $tenantId) {
    if (method_exists($user, 'tenants')) {
        return $user->tenants()->where('id', $tenantId)->exists();
    }

    return true;
});
