<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Course\StoreCourseRequest;
use App\Http\Requests\Academic\Course\UpdateCourseRequest;
use App\Models\Academic\Course;
use App\Repositories\Academic\CourseRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(
        protected CourseRepository $courseRepository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $courses = $this->courseRepository->list($request->all());

        return response()->json([
            'status' => 200,
            'message' => __('messages.courses.listed'),
            'data' => $courses,
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        $courses = $this->courseRepository->active($request->all());

        return response()->json([
            'status' => 200,
            'message' => __('messages.courses.active_listed'),
            'data' => $courses,
        ]);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseRepository->create($request->validated());

        return response()->json([
            'status' => 201,
            'message' => __('messages.courses.created'),
            'data' => $course,
        ], 201);
    }

    public function show(Course $course): JsonResponse
    {
        $course = $this->courseRepository->find($course);

        return response()->json([
            'status' => 200,
            'message' => __('messages.courses.shown'),
            'data' => $course,
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $course = $this->courseRepository->update($course, $request->validated());

        return response()->json([
            'status' => 200,
            'message' => __('messages.courses.updated'),
            'data' => $course,
        ]);
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->courseRepository->delete($course);

        return response()->json([
            'status' => 200,
            'message' => __('messages.courses.deleted'),
            'data' => null,
        ]);
    }
}
