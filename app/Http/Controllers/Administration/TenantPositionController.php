<?php

namespace App\Http\Controllers\Administration;

use App\Http\Requests\Administration\Tenant_position\TenantPositionSyncRequest;
use App\Repositories\Administration\TenantPositionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TenantPositionController
{
    public function __construct(
        protected TenantPositionRepository $tenantPositionRepository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->tenantPositionRepository->list($request);

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('administration/tenant_position.messages.listed'),
                'data' => $data,
                'error' => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('administration/tenant_position.messages.exception'),
                'data' => [],
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function indexByStatus(int $status): JsonResponse
    {
        try {
            $data = $this->tenantPositionRepository->viewAllByStatus($status);

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('administration/tenant_position.messages.listed'),
                'data' => $data,
                'error' => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('administration/tenant_position.messages.exception'),
                'data' => [],
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function indexByTenant(string $tenantId): JsonResponse
    {
        try {
            $data = $this->tenantPositionRepository->viewAllByTenant($tenantId);

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('administration/tenant_position.messages.retrieved'),
                'data' => $data ?? [
                        'tenant_id' => $tenantId,
                        'tenant' => null,
                        'positions' => [],
                    ],
                'error' => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('administration/tenant_position.messages.exception'),
                'data' => [],
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function sync(TenantPositionSyncRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $positions = $validated['positions'] ?? [];

            foreach ($positions as $index => $position) {
                if ($request->hasFile("positions.$index.signature")) {
                    $validated['positions'][$index]['signature'] = $request->file("positions.$index.signature");
                }
            }

            $data = $this->tenantPositionRepository->syncByTenant($validated);

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('administration/tenant_position.messages.synced'),
                'data' => $data,
                'error' => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('administration/tenant_position.messages.exception'),
                'data' => [],
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
