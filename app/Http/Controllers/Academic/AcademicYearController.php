<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AcademicYear\StoreAcademicYearRequest;
use App\Http\Requests\Academic\AcademicYear\UpdateAcademicYearRequest;
use App\Models\Academic\AcademicYear;
use App\Repositories\Academic\AcademicYearRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AcademicYearController extends Controller
{
    public function __construct(
        private AcademicYearRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['tenant_id', 'q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_years.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $academicYear = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.academic_years.created'),
            'data' => $academicYear,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(AcademicYear $academicYear): JsonResponse
    {
        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_years.shown'),
            'data' => $academicYear,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): JsonResponse
    {
        $academicYear = $this->repo->update(
            $academicYear,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_years.updated'),
            'data' => $academicYear,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(AcademicYear $academicYear): JsonResponse
    {
        $this->repo->delete($academicYear);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_years.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
