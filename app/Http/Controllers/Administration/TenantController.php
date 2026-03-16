<?php

namespace App\Http\Controllers\Administration;


use App\Http\Requests\Administration\tenant\TenantRequest;
use App\Repositories\Administration\TenantRepository;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TenantController
{
    public function __construct(
        protected TenantRepository $tenantRepository
    ) {
    }

    public function index(): JsonResponse
    {
        try {
            $data = $this->tenantRepository->viewAll();

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => __('administration/tenant.messages.listed'),
                'data'    => $data,
                'error'   => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('administration/tenant.messages.exception'),
                'data'    => [],
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function indexByStatus(int $status): JsonResponse
    {
        try {
            $data = $this->tenantRepository->viewAllByStatus($status);

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => __('administration/tenant.messages.listed'),
                'data'    => $data,
                'error'   => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('administration/tenant.messages.exception'),
                'data'    => [],
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $data = $this->tenantRepository->showById($id);

            if (! $data) {
                return response()->json([
                    'code'    => Response::HTTP_NOT_FOUND,
                    'message' => __('administration/tenant.messages.not_found'),
                    'data'    => [],
                    'error'   => __('administration/tenant.messages.not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => __('administration/tenant.messages.retrieved'),
                'data'    => $data,
                'error'   => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('administration/tenant.messages.exception'),
                'data'    => [],
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(TenantRequest $request): JsonResponse
    {
        try {
            $data = $this->tenantRepository->create($request->validated());

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => __('administration/tenant.messages.created'),
                'data'    => $data,
                'error'   => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('administration/tenant.messages.exception'),
                'data'    => [],
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(string $id, TenantRequest $request): JsonResponse
    {
        try {
            $data = $this->tenantRepository->update($id, $request->validated());

            if (! $data) {
                return response()->json([
                    'code'    => Response::HTTP_NOT_FOUND,
                    'message' => __('administration/tenant.messages.not_found'),
                    'data'    => [],
                    'error'   => __('administration/tenant.messages.not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => __('administration/tenant.messages.updated'),
                'data'    => $data,
                'error'   => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('administration/tenant.messages.exception'),
                'data'    => [],
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
