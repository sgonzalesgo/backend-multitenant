<?php

namespace App\Http\Controllers\Administration;

// Global import
use App\Events\Chat\ChatMessageDeleted;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

// local import
use App\Http\Controllers\Controller;
use App\Models\Administration\Tenant;
use App\Events\Chat\ChatMessageCreated;
use App\Events\Chat\ChatMessageUpdated;
use App\Events\Chat\ChatConversationRead;
use App\Repositories\Administration\DirectMessageRepository;
use App\Services\Administration\NotificationService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DirectMessageController extends Controller
{
    public function __construct(
        private readonly DirectMessageRepository $dm,
        private readonly NotificationService $notificationService
    ) {}

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

        $tenantId = (string) $tenant->id;
        $senderId = (string) $request->user()->id;
        $senderName = (string) ($request->user()->name ?? 'Usuario');

        $message = $this->dm->createMessage(
            tenantId: $tenantId,
            conversationId: $conversationId,
            senderId: $senderId,
            body: $data['body']
        );

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
            "dm.{$conversationId}",
        ];

        broadcast(new ChatMessageCreated($channels, $payload))->toOthers();

        // Crear notificación persistente + realtime
        $preview = trim((string) $data['body']);
        $preview = mb_strimwidth($preview, 0, 120, '...');

        $this->notificationService->sendToUser(
            userId: $recipientId,
            tenantId: $tenantId,
            type: 'chat.dm.message',
            title: 'Nuevo mensaje',
            message: "{$senderName} te envió un mensaje: {$preview}",
            module: 'chat',
            route: 'apps/chat',
            payload: [
                'kind' => 'dm',
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'sender_name' => $senderName,
                'message_id' => $message['id'] ?? null,
            ]
        );

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
        try {
            $tenant = Tenant::current();
            abort_unless($tenant, 400, 'Tenant no inicializado.');

            $userId = (string) $request->user()->id;
            $tenantId = (string) $tenant->id;

            $this->dm->assertParticipant($tenantId, $conversationId, $userId);

            $lastReadAt = $this->dm->markReadAt($tenantId, $conversationId, $userId);
            $otherUserId = $this->dm->getOtherUserId($tenantId, $conversationId, $userId);

            $payload = [
                'type' => 'dm',
                'tenant_id' => $tenantId,
                'conversation_id' => $conversationId,
                'user_id' => $userId,
                'last_read_at' => $lastReadAt,
            ];

            $channels = [
                "inbox.tenant.{$tenantId}.user.{$userId}",
                "inbox.tenant.{$tenantId}.user.{$otherUserId}",
                "dm.{$conversationId}",
            ];

            broadcast(new ChatConversationRead($channels, $payload))->toOthers();

            return response()->json([
                'conversation_id' => $conversationId,
                'user_id' => $userId,
                'last_read_at' => $lastReadAt,
            ]);
        } catch (\Throwable $e) {
            throw new HttpException(500, $e);
        }
    }

    public function updateMessage(Request $request, string $conversationId, string $messageId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $userId = (string) $request->user()->id;
        $tenantId = (string) $tenant->id;

        $this->dm->assertParticipant($tenantId, $conversationId, $userId);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $this->dm->updateMessage(
            tenantId: $tenantId,
            conversationId: $conversationId,
            messageId: $messageId,
            senderId: $userId,
            body: $data['body']
        );

        $recipientId = $this->dm->getOtherUserId($tenantId, $conversationId, $userId);

        $payload = [
            'type' => 'dm',
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'message' => $message,
        ];

        $channels = [
            "inbox.tenant.{$tenantId}.user.{$recipientId}",
            "inbox.tenant.{$tenantId}.user.{$userId}",
            "dm.{$conversationId}",
        ];

        broadcast(new ChatMessageUpdated($channels, $payload))->toOthers();

        return response()->json(['data' => $message]);
    }

    public function deleteMessage(Request $request, string $conversationId, string $messageId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $userId = (string) $request->user()->id;
        $tenantId = (string) $tenant->id;

        $this->dm->assertParticipant($tenantId, $conversationId, $userId);

        $recipientId = $this->dm->getOtherUserId($tenantId, $conversationId, $userId);

        $this->dm->deleteMessage(
            tenantId: $tenantId,
            conversationId: $conversationId,
            messageId: $messageId,
            senderId: $userId
        );

        $payload = [
            'type' => 'dm',
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
        ];

        $channels = [
            "inbox.tenant.{$tenantId}.user.{$recipientId}",
            "inbox.tenant.{$tenantId}.user.{$userId}",
            "dm.{$conversationId}",
        ];

        broadcast(new ChatMessageDeleted($channels, $payload))->toOthers();

        return response()->json(['message' => 'Mensaje eliminado.']);
    }

    public function hide(Request $request, string $conversationId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $this->dm->hideConversation(
            tenantId: (string) $tenant->id,
            conversationId: $conversationId,
            userId: (string) $request->user()->id
        );

        return response()->json([
            'message' => 'Conversación ocultada correctamente.'
        ]);
    }

    public function unhide(Request $request, string $conversationId): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $this->dm->unhideConversation(
            tenantId: (string) $tenant->id,
            conversationId: $conversationId,
            userId: (string) $request->user()->id
        );

        return response()->json([
            'message' => 'Conversación restaurada correctamente.'
        ]);
    }
    public function hidden(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400);

        return response()->json([
            'data' => $this->dm->listHiddenConversations(
                tenantId: (string) $tenant->id,
                userId: (string) $request->user()->id
            )
        ]);
    }
}

