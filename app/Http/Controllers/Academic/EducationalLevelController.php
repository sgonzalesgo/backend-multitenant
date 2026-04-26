<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\EducationalLevel\StoreEducationalLevelRequest;
use App\Http\Requests\Academic\EducationalLevel\UpdateEducationalLevelRequest;
use App\Models\Academic\EducationalLevel;
use App\Repositories\Academic\EducationalLevelRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EducationalLevelController extends Controller
{
    public function __construct(private EducationalLevelRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list($request->only(['q', 'sort', 'dir', 'per_page']));

        return response()->json([
            'code' => 200,
            'message' => __('messages.educational_levels.listed'),
            'data' => $data,
            'error' => null,
        ]);
    }

    public function active(): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.educational_levels.active_listed'),
            'data' => $this->repo->active(),
            'error' => null,
        ]);
    }

    public function store(StoreEducationalLevelRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 201,
            'message' => __('messages.educational_levels.created'),
            'data' => $this->repo->create($request->validated()),
            'error' => null,
        ], 201);
    }

    public function show(EducationalLevel $educationalLevel): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.educational_levels.shown'),
            'data' => $this->repo->findOrFail($educationalLevel),
            'error' => null,
        ]);
    }

    public function update(
        UpdateEducationalLevelRequest $request,
        EducationalLevel $educationalLevel
    ): JsonResponse {
        return response()->json([
            'code' => 200,
            'message' => __('messages.educational_levels.updated'),
            'data' => $this->repo->update($educationalLevel, $request->validated()),
            'error' => null,
        ]);
    }

    public function destroy(EducationalLevel $educationalLevel): JsonResponse
    {
        $this->repo->delete($educationalLevel);

        return response()->json([
            'code' => 200,
            'message' => __('messages.educational_levels.deleted'),
            'data' => null,
            'error' => null,
        ]);
    }
}
