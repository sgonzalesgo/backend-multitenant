<?php

namespace App\Http\Controllers\Administration;

use App\Http\Requests\Administration\tenant_position\TenantPositionRequest;
use App\Repositories\Administration\TenantPositionRepository;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TenantPositionController
{
    public function __construct(
        protected TenantPositionRepository $tenantPositionRepository
    ) {
    }

    public function index(): JsonResponse
    {
        try {
            $data = $this->tenantPositionRepository->viewAll();

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

    public function show(string $id): JsonResponse
    {
        try {
            $data = $this->tenantPositionRepository->showById($id);

            if (! $data) {
                return response()->json([
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => __('administration/tenant_position.messages.not_found'),
                    'data' => [],
                    'error' => __('administration/tenant_position.messages.not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('administration/tenant_position.messages.retrieved'),
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

    public function store(TenantPositionRequest $request): JsonResponse
    {
        try {
            $data = $this->tenantPositionRepository->create($request->validated());

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('administration/tenant_position.messages.created'),
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

    public function update(string $id, TenantPositionRequest $request): JsonResponse
    {
        try {
            $data = $this->tenantPositionRepository->update($id, $request->validated());

            if (! $data) {
                return response()->json([
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => __('administration/tenant_position.messages.not_found'),
                    'data' => [],
                    'error' => __('administration/tenant_position.messages.not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('administration/tenant_position.messages.updated'),
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
