<?php

namespace App\Http\Controllers\Academic\QualitativeEvaluation;

use App\Http\Controllers\Controller;


use App\Http\Requests\Academic\QualitativeEvaluation\QualitativeEvaluationArea\StoreQualitativeEvaluationAreaRequest;
use App\Http\Requests\Academic\QualitativeEvaluation\QualitativeEvaluationArea\UpdateQualitativeEvaluationAreaRequest;
use App\Models\Academic\QualitativeEvaluationArea;
use App\Repositories\Academic\QualitativeEvaluation\QualitativeEvaluationAreaRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualitativeEvaluationAreaController extends Controller
{
    public function __construct(
        protected QualitativeEvaluationAreaRepository $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_areas.listed'),
            'data' => $this->repository->list($request->all()),
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_areas.active_listed'),
            'data' => $this->repository->active($request->all()),
        ]);
    }

    public function show(QualitativeEvaluationArea $qualitativeEvaluationArea): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_areas.shown'),
            'data' => $this->repository->find($qualitativeEvaluationArea),
        ]);
    }

    public function store(StoreQualitativeEvaluationAreaRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 201,
            'message' => __('messages.qualitative_evaluation_areas.created'),
            'data' => $this->repository->create($request->validated()),
        ], 201);
    }

    public function update(
        UpdateQualitativeEvaluationAreaRequest $request,
        QualitativeEvaluationArea $qualitativeEvaluationArea
    ): JsonResponse {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_areas.updated'),
            'data' => $this->repository->update(
                $qualitativeEvaluationArea,
                $request->validated()
            ),
        ]);
    }

    public function destroy(QualitativeEvaluationArea $qualitativeEvaluationArea): JsonResponse
    {
        $this->repository->delete($qualitativeEvaluationArea);

        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_areas.deleted'),
        ]);
    }
}
