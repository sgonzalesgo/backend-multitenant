<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\EvaluationPeriod\StoreEvaluationPeriodRequest;
use App\Http\Requests\Academic\EvaluationPeriod\UpdateEvaluationPeriodRequest;
use App\Models\Academic\EvaluationPeriod;
use App\Repositories\Academic\EvaluationPeriodRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EvaluationPeriodController extends Controller
{
    public function __construct(
        private EvaluationPeriodRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.evaluation_periods.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreEvaluationPeriodRequest $request): JsonResponse
    {
        $evaluationPeriod = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.evaluation_periods.created'),
            'data' => $evaluationPeriod,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(EvaluationPeriod $evaluationPeriod): JsonResponse
    {
        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.evaluation_periods.shown'),
            'data' => $evaluationPeriod,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateEvaluationPeriodRequest $request, EvaluationPeriod $evaluationPeriod): JsonResponse
    {
        $evaluationPeriod = $this->repo->update(
            $evaluationPeriod,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.evaluation_periods.updated'),
            'data' => $evaluationPeriod,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(EvaluationPeriod $evaluationPeriod): JsonResponse
    {
        $this->repo->delete($evaluationPeriod);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.evaluation_periods.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
