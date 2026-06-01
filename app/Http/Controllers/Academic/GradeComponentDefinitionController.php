<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\GradeComponentDefinition\GradeComponentDefinitionIndexRequest;
use App\Http\Requests\Academic\GradeComponentDefinition\StoreGradeComponentDefinitionRequest;
use App\Http\Requests\Academic\GradeComponentDefinition\UpdateGradeComponentDefinitionRequest;
use App\Models\Academic\GradeComponentDefinition;
use App\Repositories\Academic\GradeComponentDefinitionRepository;
use Illuminate\Http\JsonResponse;

class GradeComponentDefinitionController extends Controller
{
    public function __construct(
        protected GradeComponentDefinitionRepository $repository
    ) {}

    public function index(GradeComponentDefinitionIndexRequest $request): JsonResponse
    {
        $data = $this->repository->index($request->validated());

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_component_definition.list_success'),
            'data' => $data,
            'error' => null,
        ]);
    }

    public function store(StoreGradeComponentDefinitionRequest $request): JsonResponse
    {
        $definition = $this->repository->store($request->validated());

        return response()->json([
            'code' => 201,
            'message' => __('messages.grade_component_definition.created_success'),
            'data' => $definition,
            'error' => null,
        ], 201);
    }

    public function show(GradeComponentDefinition $gradeComponentDefinition): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_component_definition.show_success'),
            'data' => $gradeComponentDefinition,
            'error' => null,
        ]);
    }

    public function update(
        UpdateGradeComponentDefinitionRequest $request,
        GradeComponentDefinition $gradeComponentDefinition
    ): JsonResponse {
        $definition = $this->repository->update(
            $gradeComponentDefinition,
            $request->validated()
        );

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_component_definition.updated_success'),
            'data' => $definition,
            'error' => null,
        ]);
    }

    public function destroy(GradeComponentDefinition $gradeComponentDefinition): JsonResponse
    {
        $this->repository->delete($gradeComponentDefinition);

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_component_definition.deleted_success'),
            'data' => null,
            'error' => null,
        ]);
    }
}
