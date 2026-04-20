<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AcademicYearEvaluationPeriod\StoreAcademicYearEvaluationPeriodRequest;
use App\Http\Requests\Academic\AcademicYearEvaluationPeriod\SyncAcademicYearEvaluationPeriodsRequest;
use App\Http\Requests\Academic\AcademicYearEvaluationPeriod\UpdateAcademicYearEvaluationPeriodRequest;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\AcademicYearEvaluationPeriod;
use App\Repositories\Academic\AcademicYearEvaluationPeriodRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AcademicYearEvaluationPeriodController extends Controller
{
    public function __construct(
        private AcademicYearEvaluationPeriodRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only([
                'q',
                'sort',
                'dir',
                'per_page',
            ])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_year_evaluation_periods.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreAcademicYearEvaluationPeriodRequest $request): JsonResponse
    {
        $academicYearEvaluationPeriod = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.academic_year_evaluation_periods.created'),
            'data' => $academicYearEvaluationPeriod,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(AcademicYearEvaluationPeriod $academicYearEvaluationPeriod): JsonResponse
    {
        $academicYearEvaluationPeriod->load([
            'academicYear:id,code,name,tenant_id,start_date,end_date,is_active',
            'evaluationPeriod:id,code,name,description,default_order,is_active',
        ]);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_year_evaluation_periods.shown'),
            'data' => $academicYearEvaluationPeriod,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateAcademicYearEvaluationPeriodRequest $request, AcademicYearEvaluationPeriod $academicYearEvaluationPeriod): JsonResponse {
        $academicYearEvaluationPeriod = $this->repo->update(
            $academicYearEvaluationPeriod,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_year_evaluation_periods.updated'),
            'data' => $academicYearEvaluationPeriod,
            'error' => null,
        ], Response::HTTP_OK);
    }
    public function syncByAcademicYear(SyncAcademicYearEvaluationPeriodsRequest $request, AcademicYear $academicYear): JsonResponse {
        $data = $this->repo->syncByAcademicYear(
            $academicYear->id,
            $request->validated('evaluation_periods')
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_year_evaluation_periods.synced'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(AcademicYearEvaluationPeriod $academicYearEvaluationPeriod): JsonResponse
    {
        $this->repo->delete($academicYearEvaluationPeriod);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_year_evaluation_periods.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
