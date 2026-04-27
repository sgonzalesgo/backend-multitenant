<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\EvaluationType\StoreEvaluationTypeRequest;
use App\Http\Requests\Academic\EvaluationType\UpdateEvaluationTypeRequest;
use App\Models\Academic\EvaluationType;
use App\Repositories\Academic\EvaluationTypeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EvaluationTypeController extends Controller
{
    public function __construct(
        private EvaluationTypeRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['tenant_id', 'q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.evaluation_types.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function active(Request $request): JsonResponse
    {
        $data = $this->repo->active(
            $request->only([
                'tenant_id',
                'q',
                'per_page',
            ])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.evaluation_types.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreEvaluationTypeRequest $request): JsonResponse
    {
        $evaluationType = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.evaluation_types.created'),
            'data' => $evaluationType,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(EvaluationType $evaluationType): JsonResponse
    {
        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.evaluation_types.shown'),
            'data' => $evaluationType,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateEvaluationTypeRequest $request, EvaluationType $evaluationType): JsonResponse
    {
        $evaluationType = $this->repo->update(
            $evaluationType,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.evaluation_types.updated'),
            'data' => $evaluationType,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(EvaluationType $evaluationType): JsonResponse
    {
        $this->repo->delete($evaluationType);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.evaluation_types.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
