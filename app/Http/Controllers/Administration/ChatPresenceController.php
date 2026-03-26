<?php

namespace App\Http\Controllers\Administration;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Administration\Tenant;
use App\Services\Administration\ChatPresenceService;

class ChatPresenceController extends Controller
{
    public function __construct(
        private readonly ChatPresenceService $chatPresence
    ) {}

    public function setActive(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $data = $request->validate([
            'kind' => ['required', 'string', 'in:dm,group'],
            'id' => ['required', 'string'],
        ]);

        $this->chatPresence->setActiveConversation(
            tenantId: (string) $tenant->id,
            userId: (string) $request->user()->id,
            kind: $data['kind'],
            id: $data['id']
        );

        return response()->json([
            'message' => 'Conversación activa registrada.'
        ]);
    }

    public function clearActive(Request $request): JsonResponse
    {
        $tenant = Tenant::current();
        abort_unless($tenant, 400, 'Tenant no inicializado.');

        $this->chatPresence->clearActiveConversation(
            tenantId: (string) $tenant->id,
            userId: (string) $request->user()->id
        );

        return response()->json([
            'message' => 'Conversación activa limpiada.'
        ]);
    }
}
