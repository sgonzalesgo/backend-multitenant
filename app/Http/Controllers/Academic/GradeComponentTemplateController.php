<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\GradeComponentTemplate\GenerateGradeComponentsRequest;
use App\Http\Requests\Academic\GradeComponentTemplate\GradeComponentTemplateIndexRequest;
use App\Http\Requests\Academic\GradeComponentTemplate\StoreGradeComponentTemplateRequest;
use App\Http\Requests\Academic\GradeComponentTemplate\UpdateGradeComponentTemplateRequest;
use App\Models\Academic\GradeComponentTemplate;
use App\Repositories\Academic\GradeComponentTemplateRepository;
use Illuminate\Http\JsonResponse;

class GradeComponentTemplateController extends Controller
{
    public function __construct(
        protected GradeComponentTemplateRepository $repository
    ) {}

    public function index(GradeComponentTemplateIndexRequest $request): JsonResponse
    {
        $data = $this->repository->index($request->validated());

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_component_template.list_success'),
            'data' => $data,
            'error' => null,
        ]);
    }

    public function store(StoreGradeComponentTemplateRequest $request): JsonResponse
    {
        $template = $this->repository->store($request->validated());

        return response()->json([
            'code' => 201,
            'message' => __('messages.grade_component_template.created_success'),
            'data' => $template,
            'error' => null,
        ], 201);
    }

    public function show(GradeComponentTemplate $gradeComponentTemplate): JsonResponse
    {
        $template = $this->repository->show($gradeComponentTemplate);

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_component_template.show_success'),
            'data' => $template,
            'error' => null,
        ]);
    }

    public function update(
        UpdateGradeComponentTemplateRequest $request,
        GradeComponentTemplate $gradeComponentTemplate
    ): JsonResponse {
        $template = $this->repository->update(
            $gradeComponentTemplate,
            $request->validated()
        );

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_component_template.updated_success'),
            'data' => $template,
            'error' => null,
        ]);
    }

    public function destroy(GradeComponentTemplate $gradeComponentTemplate): JsonResponse
    {
        $this->repository->delete($gradeComponentTemplate);

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_component_template.deleted_success'),
            'data' => null,
            'error' => null,
        ]);
    }

    public function generateComponents(
        GenerateGradeComponentsRequest $request,
        GradeComponentTemplate $gradeComponentTemplate
    ): JsonResponse {
        $components = $this->repository->generateComponents(
            $gradeComponentTemplate,
            $request->validated()
        );

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_component_template.components_generated_success'),
            'data' => $components,
            'error' => null,
        ]);
    }
}
