<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Requests\General\AcademicNonWorkingDay\StoreAcademicNonWorkingDayRequest;
use App\Http\Requests\General\AcademicNonWorkingDay\UpdateAcademicNonWorkingDayRequest;
use App\Models\General\AcademicNonWorkingDay;
use App\Repositories\General\AcademicNonWorkingDayRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AcademicNonWorkingDayController extends Controller
{
    public function __construct(
        protected AcademicNonWorkingDayRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_non_working_days.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function active(Request $request): JsonResponse
    {
        $data = $this->repo->active(
            $request->only([
                'academic_year_id',
                'affects_attendance',
                'affects_calendar',
            ])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_non_working_days.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreAcademicNonWorkingDayRequest $request): JsonResponse
    {
        $academicNonWorkingDay = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.academic_non_working_days.created'),
            'data' => $academicNonWorkingDay,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(AcademicNonWorkingDay $academicNonWorkingDay): JsonResponse
    {
        $academicNonWorkingDay = $this->repo->find($academicNonWorkingDay);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_non_working_days.shown'),
            'data' => $academicNonWorkingDay,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(
        UpdateAcademicNonWorkingDayRequest $request,
        AcademicNonWorkingDay $academicNonWorkingDay
    ): JsonResponse {
        $academicNonWorkingDay = $this->repo->update(
            $academicNonWorkingDay,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_non_working_days.updated'),
            'data' => $academicNonWorkingDay,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(AcademicNonWorkingDay $academicNonWorkingDay): JsonResponse
    {
        $this->repo->delete($academicNonWorkingDay);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_non_working_days.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
