<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AcademicSchedule\StoreAcademicScheduleRequest;
use App\Http\Requests\Academic\AcademicSchedule\UpdateAcademicScheduleRequest;
use App\Models\Academic\AcademicSchedule;
use App\Repositories\Academic\AcademicScheduleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AcademicScheduleController extends Controller
{
    public function __construct(
        protected AcademicScheduleRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_schedules.listed'),
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
            'message' => __('messages.academic_schedules.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreAcademicScheduleRequest $request): JsonResponse
    {
        $academicSchedule = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.academic_schedules.created'),
            'data' => $academicSchedule,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(AcademicSchedule $academicSchedule): JsonResponse
    {
        $academicSchedule = $this->repo->find($academicSchedule);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_schedules.shown'),
            'data' => $academicSchedule,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(
        UpdateAcademicScheduleRequest $request,
        AcademicSchedule $academicSchedule
    ): JsonResponse {
        $academicSchedule = $this->repo->update(
            $academicSchedule,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_schedules.updated'),
            'data' => $academicSchedule,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(AcademicSchedule $academicSchedule): JsonResponse
    {
        $this->repo->delete($academicSchedule);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_schedules.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
