<?php
//
//namespace App\Repositories\Administration;
//
//use Illuminate\Contracts\Pagination\LengthAwarePaginator;
//use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Str;
//
//class GroupMessageRepository
//{
//    public function paginateMessages(string $tenantId, string $groupId, int $perPage = 30): LengthAwarePaginator
//    {
//        return DB::table('group_messages as m')
//            ->join('users as u', 'u.id', '=', 'm.user_id')
//            ->where('m.tenant_id', $tenantId)
//            ->where('m.group_id', $groupId)
//            ->select([
//                'm.id',
//                'm.group_id',
//                'm.user_id',
//                'u.name as user_name',
//                'm.body',
//                'm.created_at',
//            ])
//            ->orderByDesc('m.created_at')
//            ->paginate($perPage);
//    }
//
//    public function createMessage(string $tenantId, string $groupId, string $userId, string $body): object
//    {
//        $id = (string) Str::uuid();
//
//        DB::table('group_messages')->insert([
//            'id' => $id,
//            'tenant_id' => $tenantId,
//            'group_id' => $groupId,
//            'user_id' => $userId,
//            'body' => $body,
//            'created_at' => now(),
//            'updated_at' => now(),
//        ]);
//
//        return DB::table('group_messages as m')
//            ->join('users as u', 'u.id', '=', 'm.user_id')
//            ->where('m.tenant_id', $tenantId)
//            ->where('m.id', $id)
//            ->select([
//                'm.id',
//                'm.group_id',
//                'm.user_id',
//                'u.name as user_name',
//                'm.body',
//                'm.created_at',
//            ])
//            ->first();
//    }
//
//    public function updateMessage(string $tenantId, string $groupId, string $messageId, string $userId, string $body): array {
//        $message = DB::table('group_messages')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('id', $messageId)
//            ->first();
//
//        abort_unless($message, 404, 'Mensaje no encontrado.');
//        abort_unless((string) $message->user_id === $userId, 403, 'Solo puedes editar tus propios mensajes.');
//
//        DB::table('group_messages')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('id', $messageId)
//            ->update([
//                'body' => $body,
//                'updated_at' => now(),
//            ]);
//
//        return (array) DB::table('group_messages as gm')
//            ->join('users as u', 'u.id', '=', 'gm.user_id')
//            ->where('gm.id', $messageId)
//            ->select([
//                'gm.id',
//                'gm.group_id',
//                'gm.user_id',
//                'u.name as user_name',
//                'gm.body',
//                'gm.created_at',
//                'gm.updated_at',
//            ])
//            ->first();
//    }
//
//    public function deleteMessage(string $tenantId, string $groupId, string $messageId, string $userId): void {
//        $message = DB::table('group_messages')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('id', $messageId)
//            ->first();
//
//        abort_unless($message, 404, 'Mensaje no encontrado.');
//        abort_unless((string) $message->user_id === $userId, 403, 'Solo puedes eliminar tus propios mensajes.');
//
//        DB::table('group_messages')
//            ->where('tenant_id', $tenantId)
//            ->where('group_id', $groupId)
//            ->where('id', $messageId)
//            ->delete();
//    }
//}
//-----------------------------------------------------------------------------------


namespace App\Repositories\Administration;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupMessageRepository
{
    public function paginateMessages(string $tenantId, string $groupId, int $perPage = 30): LengthAwarePaginator
    {
        return DB::table('group_messages as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('m.tenant_id', $tenantId)
            ->where('m.group_id', $groupId)
            ->select([
                'm.id',
                'm.group_id',
                'm.user_id',
                'u.name as user_name',
                'm.body',
                'm.created_at',
                'm.updated_at',
            ])
            ->orderByDesc('m.created_at')
            ->paginate($perPage);
    }

    public function createMessage(string $tenantId, string $groupId, string $userId, string $body): object
    {
        $id = (string)Str::uuid();

        DB::table('group_messages')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'group_id' => $groupId,
            'user_id' => $userId,
            'body' => $body,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Si estaba oculto para algún miembro aceptado, vuelve a mostrarse.
        DB::table('group_members')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('status', 'accepted')
            ->update([
                'hidden_at' => null,
                'updated_at' => now(),
            ]);

        return DB::table('group_messages as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('m.tenant_id', $tenantId)
            ->where('m.id', $id)
            ->select([
                'm.id',
                'm.group_id',
                'm.user_id',
                'u.name as user_name',
                'm.body',
                'm.created_at',
                'm.updated_at',
            ])
            ->first();
    }

    public function updateMessage(string $tenantId, string $groupId, string $messageId, string $userId, string $body): array
    {
        $message = DB::table('group_messages')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('id', $messageId)
            ->first();

        abort_unless($message, 404, 'Mensaje no encontrado.');
        abort_unless((string)$message->user_id === $userId, 403, 'Solo puedes editar tus propios mensajes.');

        DB::table('group_messages')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('id', $messageId)
            ->update([
                'body' => $body,
                'updated_at' => now(),
            ]);

        return (array)DB::table('group_messages as gm')
            ->join('users as u', 'u.id', '=', 'gm.user_id')
            ->where('gm.id', $messageId)
            ->select([
                'gm.id',
                'gm.group_id',
                'gm.user_id',
                'u.name as user_name',
                'gm.body',
                'gm.created_at',
                'gm.updated_at',
            ])
            ->first();
    }

    public function deleteMessage(string $tenantId, string $groupId, string $messageId, string $userId): void
    {
        $message = DB::table('group_messages')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('id', $messageId)
            ->first();

        abort_unless($message, 404, 'Mensaje no encontrado.');
        abort_unless((string)$message->user_id === $userId, 403, 'Solo puedes eliminar tus propios mensajes.');

        DB::table('group_messages')
            ->where('tenant_id', $tenantId)
            ->where('group_id', $groupId)
            ->where('id', $messageId)
            ->delete();
    }
}
