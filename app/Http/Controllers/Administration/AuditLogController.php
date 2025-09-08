<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\AuditLogIndexRequest;
use App\Http\Requests\Administration\AuditLogStoreRequest;
use App\Repositories\Administration\AuditLogRepository;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogRepository $repo) {}

    /** GET /v1/audits */
    public function index(AuditLogIndexRequest $request): JsonResponse
    {
        $data = $this->repo->list($request->validated());

        return response()->json([
            'code'    => Response::HTTP_OK,
            'message' => __('messages.listed', ['entity' => 'audit logs']),
            'data'    => $data,
            'error'   => null,
        ], Response::HTTP_OK);
    }

    /** POST /v1/audits (opcional: logs ad-hoc de dominio) */
    public function store(AuditLogStoreRequest $request): JsonResponse
    {
        $v = $request->validated();

        $log = $this->repo->log(
            actor: $request->user(),
            event: $v['event'],
            subject: [
                'type' => $v['auditable_type'] ?? null,
                'id'   => $v['auditable_id']   ?? null,
            ],
            description: $v['description'] ?? null,
            changes: [
                'old' => $v['old_values'] ?? null,
                'new' => $v['new_values'] ?? null,
            ],
            tenantId: $v['tenant_id'] ?? \App\Models\Administration\Tenant::current()?->id,
            meta: $v['meta'] ?? []
        );

        return response()->json([
            'code'    => Response::HTTP_CREATED,
            'message' => __('messages.created', ['entity' => 'audit log']),
            'data'    => $log,
            'error'   => null,
        ], Response::HTTP_CREATED);
    }
}
