<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Classroom\StoreClassroomRequest;
use App\Http\Requests\Academic\Classroom\UpdateClassroomRequest;
use App\Models\Academic\Classroom;
use App\Repositories\Academic\ClassroomRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClassroomController extends Controller
{
    public function __construct(private ClassroomRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list($request->only(['q','sort','dir','per_page']));

        return response()->json([
            'code' => 200,
            'message' => __('messages.classrooms.listed'),
            'data' => $data,
            'error' => null,
        ]);
    }

    public function active(): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.classrooms.active_listed'),
            'data' => $this->repo->active(),
            'error' => null,
        ]);
    }

    public function store(StoreClassroomRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 201,
            'message' => __('messages.classrooms.created'),
            'data' => $this->repo->create($request->validated()),
            'error' => null,
        ], 201);
    }

    public function show(Classroom $classroom): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.classrooms.shown'),
            'data' => $this->repo->findOrFail($classroom),
            'error' => null,
        ]);
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.classrooms.updated'),
            'data' => $this->repo->update($classroom, $request->validated()),
            'error' => null,
        ]);
    }

    public function destroy(Classroom $classroom): JsonResponse
    {
        $this->repo->delete($classroom);

        return response()->json([
            'code' => 200,
            'message' => __('messages.classrooms.deleted'),
            'data' => null,
            'error' => null,
        ]);
    }
}
