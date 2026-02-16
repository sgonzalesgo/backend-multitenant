<?php

namespace App\Http\Controllers\Administration;

// Global import
use App\Events\Chat\ChatConversationRead;
use App\Events\Chat\ChatMessageCreated;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

// local import
use App\Http\Controllers\Controller;
use App\Models\Administration\Tenant;
use App\Events\Direct\DirectMessageSent;
use App\Events\Direct\DirectConversationRead;
use App\Repositories\Administration\DirectMessageRepository;


class DirectMessageController extends Controller
{
    public function __construct(private readonly DirectMessageRepository $dm) {}

    /**
     * START: crea o retorna conversación DM con target_user_id (mismo tenant).
     */
    public function start(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $data = $request->validate([
            'target_user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $conv = $this->dm->startConversation(
            tenantId: (string) $tenant->id,
            actorId: (string) $request->user()->id,
            targetUserId: (string) $data['target_user_id']
        );

        return response()->json([
            'data' => $conv,
            'channel' => "private-dm.{$conv['id']}",
        ]);
    }

    /**
     * MESSAGES: retorna mensajes paginados de la conversación.
     */
    public function messages(Request $request, string $conversationId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $this->dm->assertParticipant((string) $tenant->id, $conversationId, (string) $request->user()->id);

        $perPage = (int) $request->query('per_page', 30);

        return response()->json(
            $this->dm->paginateMessages((string) $tenant->id, $conversationId, $perPage)
        );
    }

    /**
     * SEND: crea mensaje DM y lo emite por websocket al otro participante.
     */
    public function send(Request $request, string $conversationId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $this->dm->assertParticipant((string) $tenant->id, $conversationId, (string) $request->user()->id);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $this->dm->createMessage(
            tenantId: (string) $tenant->id,
            conversationId: $conversationId,
            senderId: (string) $request->user()->id,
            body: $data['body']
        );

        $tenantId = (string) $tenant->id;
        $senderId = (string) $request->user()->id;

        $recipientId = $this->dm->getOtherUserId($tenantId, $conversationId, $senderId);

        $payload = [
            'type' => 'dm',
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId,
            'message' => $message,
        ];

        // Emitir al inbox del receptor (para sidebar + unread en tiempo real)
        $channels = [
            "inbox.tenant.{$tenantId}.user.{$recipientId}",
        ];

        // (Opcional pero recomendado) emitir también al inbox del emisor para sincronizar varias pestañas
        $channels[] = "inbox.tenant.{$tenantId}.user.{$senderId}";

        // (Opcional) emitir al canal de la conversación, solo útil si el chat está abierto y escuchando dm.{id}
        $channels[] = "dm.{$conversationId}";

        broadcast(new ChatMessageCreated($channels, $payload))->toOthers();

        return response()->json(['data' => $message], 201);
    }

    /**
     * INDEX: lista mis conversaciones DM (sidebar tipo Slack).
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $perPage = (int) $request->query('per_page', 30);

        return response()->json(
            $this->dm->listMyConversations(
                tenantId: (string) $tenant->id,
                userId: (string) $request->user()->id,
                perPage: $perPage
            )
        );
    }

    /**
     * READ: marca la conversación como leída (last_read_at = now()).
     */
    public function read(Request $request, string $conversationId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $userId = (string) $request->user()->id;
        $tenantId = (string) $tenant->id;

        $this->dm->assertParticipant($tenantId, $conversationId, $userId);

        $tenantId = (string) $tenant->id;
        $userId   = (string) $request->user()->id;

        // ya validaste participante...
        $lastReadAt = $this->dm->markReadAt($tenantId, $conversationId, $userId);

        // Necesitamos el otro user para actualizar su sidebar también
        $otherUserId = $this->dm->getOtherUserId($tenantId, $conversationId, $userId);

        $payload = [
            'type' => 'dm',
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'last_read_at' => $lastReadAt,
        ];

        // Canales a notificar
        $channels = [
            // inbox del lector (para multi-tab y dejar unread=0 en todas las pestañas)
            "inbox.tenant.{$tenantId}.user.{$userId}",

            // inbox del otro (para que su UI sepa que “ya leyeron” si decides mostrar seen)
            "inbox.tenant.{$tenantId}.user.{$otherUserId}",

            // canal de conversación si está abierta en otra pestaña
            "dm.{$conversationId}",
        ];

        broadcast(new ChatConversationRead($channels, $payload))->toOthers();

        return response()->json([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'last_read_at' => $lastReadAt,
        ]);
    }
}
