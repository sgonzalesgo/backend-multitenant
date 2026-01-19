<?php

namespace App\Repositories\Administration;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupMessageRepository
{
    public function paginateMessages(string $groupId, int $perPage = 30): LengthAwarePaginator
    {
        return DB::table('group_messages as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('m.group_id', $groupId)
            ->select([
                'm.id',
                'm.group_id',
                'm.user_id',
                'u.name as user_name',
                'm.body',
                'm.created_at',
            ])
            ->orderByDesc('m.created_at')
            ->paginate($perPage);
    }

    public function createMessage(string $groupId, string $userId, string $body): object
    {
        $id = (string) Str::uuid();

        DB::table('group_messages')->insert([
            'id' => $id,
            'group_id' => $groupId,
            'user_id' => $userId,
            'body' => $body,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('group_messages as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('m.id', $id)
            ->select([
                'm.id',
                'm.group_id',
                'm.user_id',
                'u.name as user_name',
                'm.body',
                'm.created_at',
            ])
            ->first();
    }
}
