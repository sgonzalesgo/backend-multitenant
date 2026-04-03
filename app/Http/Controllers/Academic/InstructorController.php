<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Instructor\StoreInstructorRequest;
use App\Http\Requests\Academic\Instructor\UpdateInstructorRequest;
use App\Models\Academic\Instructor;
use App\Repositories\Academic\InstructorRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstructorController extends Controller
{
    public function __construct(
        private InstructorRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.instructors.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreInstructorRequest $request): JsonResponse
    {
        $instructor = $this->repo->create(
            $request->validated(),
            $request->file('photo')
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.instructors.created'),
            'data' => $instructor,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(Instructor $instructor): JsonResponse
    {
        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.instructors.shown'),
            'data' => $instructor->load(['person', 'tenant:id,name']),
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateInstructorRequest $request, Instructor $instructor): JsonResponse
    {
        $instructor = $this->repo->update(
            $instructor,
            $request->validated(),
            $request->file('photo')
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.instructors.updated'),
            'data' => $instructor,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(Instructor $instructor): JsonResponse
    {
        $this->repo->delete($instructor);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.instructors.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
