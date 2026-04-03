<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\AuditLogIndexRequest;
use App\Http\Requests\Administration\AuditLogStoreRequest;
use App\Models\Administration\Permission;
use App\Models\Administration\Role;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Models\General\Person;
// use App\Models\Construction\Constructor; // cuando aplique
use App\Repositories\Administration\AuditLogRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogRepository $repo) {}

    /** GET /v1/audits
     * @throws Exception
     */
    public function index(AuditLogIndexRequest $request): JsonResponse
    {
        $data = $this->repo->list($request->validated());

        try {
            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('messages.listed', ['entity' => 'audit logs']),
                'data' => $data,
                'error' => null,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /** GET /v1/audits/history/{type}/{id} */
    public function history(Request $request, string $type, string $id): JsonResponse
    {
        $auditableType = $this->resolveAuditableType($type);

        if (! $auditableType) {
            return response()->json([
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => __('messages.history.invalid', ['entity' => 'audit type']),
                'data' => null,
                'error' => [
                    'type' => ['messages.history.invalid_type'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $this->repo->historyBySubject(
            auditableType: $auditableType,
            auditableId: $id,
            filters: $request->only(['per_page', 'sort', 'dir'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.history.listed', ['entity' => 'audit logs']),
            'data' => $data,
            'error' => null,
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
                'id'   => $v['auditable_id'] ?? null,
            ],
            description: $v['description'] ?? null,
            changes: [
                'old' => $v['old_values'] ?? null,
                'new' => $v['new_values'] ?? null,
            ],
            tenantId: $v['tenant_id'] ?? Tenant::current()?->id,
            meta: $v['meta'] ?? []
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.created', ['entity' => 'audit log']),
            'data' => $log,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    private function resolveAuditableType(string $type): ?string
    {
        return [
            'persons' => Person::class,
            'users' => User::class,
            'permissions' => Permission::class,
            'roles' => Role::class,
            'tenants' => Tenant::class,
        ][$type] ?? null;
    }
}
