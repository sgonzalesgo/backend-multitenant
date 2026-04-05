<?php

namespace App\Http\Controllers\Administration;

use App\Http\Requests\Administration\position\PositionRequest;
use App\Repositories\Administration\PositionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PositionController
{
    public function __construct(
        protected PositionRepository $positionRepository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->positionRepository->paginate($request->all());

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('validation/administration/position.messages.listed'),
                'data' => $data,
                'error' => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('validation/administration/position.messages.exception'),
                'data' => [],
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function indexByStatus(int $status, Request $request): JsonResponse
    {
        try {
            $data = $this->positionRepository->paginateByStatus($status, $request->all());

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('validation/administration/position.messages.listed'),
                'data' => $data,
                'error' => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('validation/administration/position.messages.exception'),
                'data' => [],
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $data = $this->positionRepository->showById($id);

            if (! $data) {
                return response()->json([
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => __('validation/administration/position.messages.not_found'),
                    'data' => [],
                    'error' => __('validation/administration/position.messages.not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('validation/administration/position.messages.retrieved'),
                'data' => $data,
                'error' => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('validation/administration/position.messages.exception'),
                'data' => [],
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(PositionRequest $request): JsonResponse
    {
        try {
            $data = $this->positionRepository->create($request->validated());

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('validation/administration/position.messages.created'),
                'data' => $data,
                'error' => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('validation/administration/position.messages.exception'),
                'data' => [],
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(string $id, PositionRequest $request): JsonResponse
    {
        try {
            $data = $this->positionRepository->update($id, $request->validated());

            if (! $data) {
                return response()->json([
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => __('validation/administration/position.messages.not_found'),
                    'data' => [],
                    'error' => __('validation/administration/position.messages.not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'code' => Response::HTTP_OK,
                'message' => __('validation/administration/position.messages.updated'),
                'data' => $data,
                'error' => null,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->json([
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => __('validation/administration/position.messages.exception'),
                'data' => [],
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
