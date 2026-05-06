<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Enrollment\StoreEnrollmentRequest;
use App\Http\Requests\Academic\Enrollment\UpdateEnrollmentRequest;
use App\Models\Academic\Enrollment;
use App\Repositories\Academic\EnrollmentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Academic\Enrollment\PrepareEnrollmentRequest;
use App\Services\Academic\EnrollmentPreparationService;

class EnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentRepository $repo,
        protected EnrollmentPreparationService $preparationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.enrollments.listed'),
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
            'message' => __('messages.enrollments.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function prepare(PrepareEnrollmentRequest $request): JsonResponse
    {
        $data = $this->preparationService->prepareByLegalId(
            $request->string('legal_id')->toString()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.enrollments.prepared'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        $enrollment = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.enrollments.created'),
            'data' => $enrollment,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(Enrollment $enrollment): JsonResponse
    {
        $enrollment = $this->repo->find($enrollment);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.enrollments.shown'),
            'data' => $enrollment,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(
        UpdateEnrollmentRequest $request,
        Enrollment $enrollment
    ): JsonResponse {
        $enrollment = $this->repo->update(
            $enrollment,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.enrollments.updated'),
            'data' => $enrollment,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(Enrollment $enrollment): JsonResponse
    {
        $this->repo->delete($enrollment);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.enrollments.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
