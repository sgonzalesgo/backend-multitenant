<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Broadcast;
use App\Models\Administration\User;

//Broadcast::channel('presence', function ($user) {
//    // Solo usuarios autenticados pueden escuchar presencia global
//    return (bool) $user;
//});

// este es globla por tenant para ver los que estan en linea
Broadcast::channel('presence.tenant.{tenantId}', function ($user, $tenantId) {
    $teamFk = config('permission.team_foreign_key', 'tenant_id');

    $ok = DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
        ->where('model_type', User::class)
        ->where('model_id', (string) $user->id)
        ->where($teamFk, (string) $tenantId)
        ->exists();

    if (! $ok) return false;

    // Esto es lo que verán los demás en el presence channel
    return [
        'id' => (string) $user->id,
        'name' => $user->name,
    ];
});


// este es para ver los mensajes de un grupo
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    return DB::table('group_members')
        ->where('group_id', (string) $groupId)
        ->where('user_id', (string) $user->id)
        ->where('status', 'accepted')
        ->exists();
});




