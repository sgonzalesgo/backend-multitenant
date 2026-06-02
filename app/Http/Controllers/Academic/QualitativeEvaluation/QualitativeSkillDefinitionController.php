<?php

namespace App\Http\Controllers\Academic\QualitativeEvaluation;

use App\Http\Controllers\Controller;

use App\Http\Requests\Academic\QualitativeEvaluation\QualitativeSkillDefinition\StoreQualitativeSkillDefinitionRequest;
use App\Http\Requests\Academic\QualitativeEvaluation\QualitativeSkillDefinition\UpdateQualitativeSkillDefinitionRequest;
use App\Models\Academic\QualitativeSkillDefinition;
use App\Repositories\Academic\QualitativeEvaluation\QualitativeSkillDefinitionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualitativeSkillDefinitionController extends Controller
{
    public function __construct(
        protected QualitativeSkillDefinitionRepository $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_skill_definitions.listed'),
            'data' => $this->repository->list($request->all()),
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_skill_definitions.active_listed'),
            'data' => $this->repository->active($request->all()),
        ]);
    }

    public function show(QualitativeSkillDefinition $qualitativeSkillDefinition): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_skill_definitions.shown'),
            'data' => $this->repository->find($qualitativeSkillDefinition),
        ]);
    }

    public function store(StoreQualitativeSkillDefinitionRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 201,
            'message' => __('messages.qualitative_skill_definitions.created'),
            'data' => $this->repository->create($request->validated()),
        ], 201);
    }

    public function update(
        UpdateQualitativeSkillDefinitionRequest $request,
        QualitativeSkillDefinition $qualitativeSkillDefinition
    ): JsonResponse {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_skill_definitions.updated'),
            'data' => $this->repository->update(
                $qualitativeSkillDefinition,
                $request->validated()
            ),
        ]);
    }

    public function destroy(QualitativeSkillDefinition $qualitativeSkillDefinition): JsonResponse
    {
        $this->repository->delete($qualitativeSkillDefinition);

        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_skill_definitions.deleted'),
        ]);
    }
}
