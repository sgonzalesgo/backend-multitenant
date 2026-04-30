<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Student\StoreStudentRequest;
use App\Http\Requests\Academic\Student\UpdateStudentRequest;
use App\Models\Academic\Student;
use App\Repositories\Academic\StudentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentController extends Controller
{
    public function __construct(
        protected StudentRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.students.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function active(Request $request): JsonResponse
    {
        $data = $this->repo->active(
            $request->only(['per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.students.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = $this->repo->create(
            $request->validated(),
            $request->file('photo')
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.students.created'),
            'data' => $student,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(Student $student): JsonResponse
    {
        $student = $this->repo->find($student);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.students.shown'),
            'data' => $student,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $student = $this->repo->update(
            $student,
            $request->validated(),
            $request->file('photo')
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.students.updated'),
            'data' => $student,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(Student $student): JsonResponse
    {
        $this->repo->delete($student);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.students.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
