<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\EnrollmentStatus\StoreEnrollmentStatusRequest;
use App\Http\Requests\Academic\EnrollmentStatus\UpdateEnrollmentStatusRequest;
use App\Models\Academic\EnrollmentStatus;
use App\Repositories\Academic\EnrollmentStatusRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnrollmentStatusController extends Controller
{
    public function __construct(
        private EnrollmentStatusRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.enrollment_statuses.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreEnrollmentStatusRequest $request): JsonResponse
    {
        $enrollmentStatus = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.enrollment_statuses.created'),
            'data' => $enrollmentStatus,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(EnrollmentStatus $enrollmentStatus): JsonResponse
    {
        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.enrollment_statuses.shown'),
            'data' => $enrollmentStatus,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateEnrollmentStatusRequest $request, EnrollmentStatus $enrollmentStatus): JsonResponse
    {
        $enrollmentStatus = $this->repo->update(
            $enrollmentStatus,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.enrollment_statuses.updated'),
            'data' => $enrollmentStatus,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(EnrollmentStatus $enrollmentStatus): JsonResponse
    {
        $this->repo->delete($enrollmentStatus);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.enrollment_statuses.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
