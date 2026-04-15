<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Requests\General\Department\StoreDepartmentRequest;
use App\Http\Requests\General\Department\UpdateDepartmentRequest;
use App\Models\General\Department;
use App\Repositories\General\DepartmentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentController extends Controller
{
    public function __construct(private DepartmentRepository $repo)
    {
    }

    public function index(Request $req): JsonResponse
    {
        $data = $this->repo->list($req->only(['q', 'sort', 'dir', 'per_page']));

        return response()->json([
            'code'    => 200,
            'message' => __('messages.departments.listed'),
            'data'    => $data,
            'error'   => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreDepartmentRequest $req): JsonResponse
    {
        $department = $this->repo->create($req);

        return response()->json([
            'code'    => 201,
            'message' => __('messages.departments.created'),
            'data'    => $department,
            'error'   => null,
        ], Response::HTTP_CREATED);
    }

    public function show(Department $department): JsonResponse
    {
        $department = $this->repo->findOrFail($department->id);

        return response()->json([
            'code'    => 200,
            'message' => __('messages.departments.shown'),
            'data'    => $department,
            'error'   => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateDepartmentRequest $req, Department $department): JsonResponse
    {
        $department = $this->repo->update($department, $req);

        return response()->json([
            'code'    => 200,
            'message' => __('messages.departments.updated'),
            'data'    => $department,
            'error'   => null,
        ], Response::HTTP_OK);
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->repo->delete($department);

        return response()->json([
            'code'    => 200,
            'message' => __('messages.departments.deleted'),
            'data'    => null,
            'error'   => null,
        ], Response::HTTP_OK);
    }
}
