<?php

namespace App\Http\Controllers\Academic\QualitativeEvaluation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\QualitativeEvaluation\QualitativeEvaluationComponent\GenerateQualitativeEvaluationComponentRequest;
use App\Models\Academic\QualitativeEvaluationComponent;
use App\Repositories\Academic\QualitativeEvaluation\QualitativeEvaluationComponentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualitativeEvaluationComponentController extends Controller
{
    public function __construct(
        protected QualitativeEvaluationComponentRepository $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_components.listed'),
            'data' => $this->repository->list($request->all()),
        ]);
    }

    public function show(QualitativeEvaluationComponent $qualitativeEvaluationComponent): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_components.shown'),
            'data' => $this->repository->find($qualitativeEvaluationComponent),
        ]);
    }

    public function generate(GenerateQualitativeEvaluationComponentRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_components.generated'),
            'data' => $this->repository->generate($request->validated()),
        ]);
    }

    public function destroy(QualitativeEvaluationComponent $qualitativeEvaluationComponent): JsonResponse
    {
        $this->repository->delete($qualitativeEvaluationComponent);

        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_components.deleted'),
        ]);
    }

    public function destroyGroup(Request $request): JsonResponse
    {
        $deleted = $this->repository->deleteGroup($request->all());

        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_components.deleted'),
            'data' => [
                'deleted' => $deleted,
            ],
        ]);
    }
}
