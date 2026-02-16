<?php

namespace App\Repositories\Administration;

use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DirectMessageRepository
{
    /**
     * Crea o retorna una conversación DM entre el usuario actual y el target (mismo tenant).
     * Guarda el par user_one_id / user_two_id en orden para evitar duplicados.
     */
    public function startConversation(string $tenantId, string $actorId, string $targetUserId): array
    {
        if ($actorId === $targetUserId) {
            abort(422, 'No puedes crear un chat contigo mismo.');
        }

        $this->assertUserBelongsToTenant($targetUserId, $tenantId);

        [$one, $two] = $this->sortedPair($actorId, $targetUserId);

        $existing = DB::table('direct_conversations')
            ->where('tenant_id', $tenantId)
            ->where('user_one_id', $one)
            ->where('user_two_id', $two)
            ->first();

        if ($existing) {
            return (array) $existing;
        }

        $id = (string) Str::uuid();

        DB::table('direct_conversations')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'user_one_id' => $one,
            'user_two_id' => $two,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('direct_conversations')->where('id', $id)->first();
    }

    /**
     * Verifica que el usuario sea participante de la conversación (y tenant).
     */
    public function assertParticipant(string $tenantId, string $conversationId, string $userId): void
    {
        $conv = DB::table('direct_conversations')
            ->select(['id', 'tenant_id', 'user_one_id', 'user_two_id'])
            ->where('id', $conversationId)
            ->first();

        abort_unless($conv, 404);
        abort_unless((string) $conv->tenant_id === $tenantId, 404);

        $isParticipant = $userId === (string) $conv->user_one_id || $userId === (string) $conv->user_two_id;
        abort_unless($isParticipant, 403);
    }

    /**
     * Paginación de mensajes por conversación.
     */
    public function paginateMessages(string $tenantId, string $conversationId, int $perPage = 30): array
    {
        $paginator = DB::table('direct_messages as dm')
            ->join('users as u', 'u.id', '=', 'dm.sender_id')
            ->where('dm.tenant_id', $tenantId)
            ->where('dm.conversation_id', $conversationId)
            ->orderByDesc('dm.created_at')
            ->select([
                'dm.id',
                'dm.conversation_id',
                'dm.sender_id',
                'u.name as sender_name',
                'dm.body',
                'dm.created_at',
            ])
            ->paginate($perPage);

        return $paginator->toArray();
    }

    /**
     * Crear mensaje DM.
     */
    public function createMessage(string $tenantId, string $conversationId, string $senderId, string $body): array
    {
        $id = (string) Str::uuid();

        DB::table('direct_messages')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'body' => $body,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (array) DB::table('direct_messages as dm')
            ->join('users as u', 'u.id', '=', 'dm.sender_id')
            ->where('dm.id', $id)
            ->select([
                'dm.id',
                'dm.conversation_id',
                'dm.sender_id',
                'u.name as sender_name',
                'dm.body',
                'dm.created_at',
            ])
            ->first();
    }

    /**
     * Valida pertenencia al tenant (Spatie teams / model_has_roles).
     */
    private function assertUserBelongsToTenant(string $userId, string $tenantId): void
    {
        $teamFk = config('permission.team_foreign_key', 'tenant_id');
        $table  = config('permission.table_names.model_has_roles', 'model_has_roles');

        $belongs = DB::table($table)
            ->where('model_type', User::class)
            ->where('model_id', $userId)
            ->where($teamFk, $tenantId)
            ->exists();

        abort_unless($belongs, 422, 'El usuario no pertenece a este colegio (tenant).');
    }

    /**
     * Ordena el par para asegurar unicidad.
     */
    private function sortedPair(string $a, string $b): array
    {
        return strcmp($a, $b) < 0 ? [$a, $b] : [$b, $a];
    }

    /**
     * Lista mis conversaciones DM con:
     * - otro usuario
     * - último mensaje
     * - last_read_at del usuario actual
     * - unread_count (mensajes del otro creados después de last_read_at)
     */
    public function listMyConversations(string $tenantId, string $userId, int $perPage = 30): array
    {
        // Último mensaje por conversación
        $lastMsgSub = DB::table('direct_messages')
            ->selectRaw('conversation_id, MAX(created_at) as last_message_at')
            ->where('tenant_id', $tenantId)
            ->groupBy('conversation_id');

        // last_read_at del usuario actual por conversación
        $readsSub = DB::table('direct_conversation_reads')
            ->select(['conversation_id', 'last_read_at'])
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId);

        $query = DB::table('direct_conversations as dc')
            ->where('dc.tenant_id', $tenantId)
            ->where(function ($q) use ($userId) {
                $q->where('dc.user_one_id', $userId)
                    ->orWhere('dc.user_two_id', $userId);
            })
            ->leftJoinSub($lastMsgSub, 'lm', fn ($j) => $j->on('lm.conversation_id', '=', 'dc.id'))
            ->leftJoin('direct_messages as dm', function ($join) use ($tenantId) {
                $join->on('dm.conversation_id', '=', 'dc.id')
                    ->on('dm.created_at', '=', 'lm.last_message_at')
                    ->where('dm.tenant_id', '=', $tenantId);
            })
            ->leftJoinSub($readsSub, 'r', fn ($j) => $j->on('r.conversation_id', '=', 'dc.id'))

            // Join al "otro" usuario SIN interpolar variables (Postgres-friendly)
            ->leftJoin('users as u_other', function ($join) use ($userId) {
                // u_other.id = (si yo soy user_one -> user_two) si no -> user_one
                $join->on('u_other.id', '=', DB::raw('CASE WHEN dc.user_one_id = ? THEN dc.user_two_id ELSE dc.user_one_id END'))
                    ->addBinding($userId, 'join');
            })

            ->select([
                'dc.id as conversation_id',
                'dc.created_at as conversation_created_at',

                'u_other.id as other_user_id',
                'u_other.name as other_user_name',
                'u_other.email as other_user_email',

                'dm.id as last_message_id',
                'dm.body as last_message_body',
                'dm.sender_id as last_message_sender_id',
                'dm.created_at as last_message_created_at',

                'r.last_read_at',
            ])

            // unread_count: cuenta mensajes del OTRO luego del last_read_at (si null, usa epoch)
            ->selectRaw(
                "
            (
                SELECT COUNT(*)
                FROM direct_messages dm2
                WHERE dm2.tenant_id = ?
                  AND dm2.conversation_id = dc.id
                  AND dm2.sender_id <> ?
                  AND dm2.created_at > COALESCE(r.last_read_at, TIMESTAMP '1970-01-01 00:00:00')
            ) as unread_count
            ",
                [$tenantId, $userId]
            )

            ->orderByRaw('COALESCE(lm.last_message_at, dc.created_at) DESC');

        return $query->paginate($perPage)->toArray();
    }

    /**
     * Marca la conversación como leída hasta "now()" para este usuario.
     * Crea el registro si no existe.
     */
    public function markReadAt(string $tenantId, string $conversationId, string $userId, ?string $readAtIso = null): string
    {
        $readAt = $readAtIso ? now()->parse($readAtIso) : now();

        DB::table('direct_conversation_reads')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'conversation_id' => $conversationId,
                'user_id' => $userId,
            ],
            [
                'last_read_at' => $readAt,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $readAt->toIso8601String();
    }

    /**
     * Devuelve el otro participante (recipient) de una conversación DM.
     */
    public function getOtherUserId(string $tenantId, string $conversationId, string $userId): string
    {
        $conv = DB::table('direct_conversations')
            ->select(['tenant_id', 'user_one_id', 'user_two_id'])
            ->where('id', $conversationId)
            ->first();

        abort_unless($conv, 404);
        abort_unless((string) $conv->tenant_id === $tenantId, 404);

        $other = $userId === (string) $conv->user_one_id
            ? (string) $conv->user_two_id
            : (string) $conv->user_one_id;

        return $other;
    }
}
