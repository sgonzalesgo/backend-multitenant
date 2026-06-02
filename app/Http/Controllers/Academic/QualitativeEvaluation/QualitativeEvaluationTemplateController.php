<?php

namespace App\Http\Controllers\Academic\QualitativeEvaluation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\QualitativeEvaluation\QualitativeEvaluationTemplate\StoreQualitativeEvaluationTemplateRequest;
use App\Http\Requests\Academic\QualitativeEvaluation\QualitativeEvaluationTemplate\UpdateQualitativeEvaluationTemplateRequest;
use App\Models\Academic\QualitativeEvaluationTemplate;
use App\Repositories\Academic\QualitativeEvaluation\QualitativeEvaluationTemplateRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualitativeEvaluationTemplateController extends Controller
{
    public function __construct(
        protected QualitativeEvaluationTemplateRepository $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_templates.listed'),
            'data' => $this->repository->list($request->all()),
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_templates.active_listed'),
            'data' => $this->repository->active($request->all()),
        ]);
    }

    public function show(QualitativeEvaluationTemplate $qualitativeEvaluationTemplate): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_templates.shown'),
            'data' => $this->repository->find($qualitativeEvaluationTemplate),
        ]);
    }

    public function store(StoreQualitativeEvaluationTemplateRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 201,
            'message' => __('messages.qualitative_evaluation_templates.created'),
            'data' => $this->repository->create($request->validated()),
        ], 201);
    }

    public function update(
        UpdateQualitativeEvaluationTemplateRequest $request,
        QualitativeEvaluationTemplate $qualitativeEvaluationTemplate
    ): JsonResponse {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_templates.updated'),
            'data' => $this->repository->update(
                $qualitativeEvaluationTemplate,
                $request->validated()
            ),
        ]);
    }

    public function destroy(QualitativeEvaluationTemplate $qualitativeEvaluationTemplate): JsonResponse
    {
        $this->repository->delete($qualitativeEvaluationTemplate);

        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_templates.deleted'),
        ]);
    }
}
