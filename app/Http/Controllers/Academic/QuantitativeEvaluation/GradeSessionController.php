<?php

namespace App\Http\Controllers\Academic\QuantitativeEvaluation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\GradeSession\OpenGradeSessionRequest;
use App\Http\Requests\Academic\GradeSession\SaveGradeSessionRequest;
use App\Models\Academic\GradeSession;
use App\Repositories\Academic\QuantitativeEvaluation\GradeSessionRepository;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Academic\GradeSession\GradeSessionIndexRequest;

class GradeSessionController extends Controller
{
    public function __construct(
        protected GradeSessionRepository $repository
    ) {}

    public function index(GradeSessionIndexRequest $request): JsonResponse
    {
        $sessions = $this->repository->index($request->validated());

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_session.list_success'),
            'data' => $sessions,
            'error' => null,
        ]);
    }

    public function open(OpenGradeSessionRequest $request): JsonResponse
    {
        $session = $this->repository->openSession($request->validated());

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_session.open_success'),
            'data' => $session,
            'error' => null,
        ]);
    }

    public function show(GradeSession $gradeSession): JsonResponse
    {
        $session = $this->repository->show($gradeSession);

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_session.show_success'),
            'data' => $session,
            'error' => null,
        ]);
    }

    public function saveGrades(
        SaveGradeSessionRequest $request,
        GradeSession $gradeSession
    ): JsonResponse {
        $session = $this->repository->saveGrades(
            $gradeSession,
            $request->validated()
        );

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_session.saved_success'),
            'data' => $session,
            'error' => null,
        ]);
    }

    public function close(GradeSession $gradeSession): JsonResponse
    {
        $session = $this->repository->closeSession($gradeSession);

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_session.closed_success'),
            'data' => $session,
            'error' => null,
        ]);
    }

    public function reopen(GradeSession $gradeSession): JsonResponse
    {
        $session = $this->repository->reopenSession($gradeSession);

        return response()->json([
            'code' => 200,
            'message' => __('messages.grade_session.reopened_success'),
            'data' => $session,
            'error' => null,
        ]);
    }
}
