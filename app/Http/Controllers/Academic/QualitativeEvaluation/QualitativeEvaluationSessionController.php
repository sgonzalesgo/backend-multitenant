<?php

namespace App\Http\Controllers\Academic\QualitativeEvaluation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\QualitativeEvaluationSession\IndexQualitativeEvaluationSessionRequest;
use App\Http\Requests\Academic\QualitativeEvaluationSession\OpenQualitativeEvaluationSessionRequest;
use App\Http\Requests\Academic\QualitativeEvaluationSession\SaveQualitativeEvaluationSessionRequest;
use App\Models\Academic\QualitativeEvaluationSession;
use App\Repositories\Academic\QualitativeEvaluation\QualitativeEvaluationSessionRepository;
use Illuminate\Http\JsonResponse;

class QualitativeEvaluationSessionController extends Controller
{
    public function __construct(
        protected QualitativeEvaluationSessionRepository $repository
    ) {}

    public function index(IndexQualitativeEvaluationSessionRequest $request)
    {
        $sessions = $this->repository->index($request->validated());

        return response()->json([
            'code' => 200,
            'message' => __('messages.success'),
            'data' => $sessions,
        ]);
    }
    public function open(OpenQualitativeEvaluationSessionRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_sessions.opened'),
            'data' => $this->repository->open(
                $request->validated()
            ),
        ]);
    }

    public function show(QualitativeEvaluationSession $qualitativeEvaluationSession): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_sessions.shown'),
            'data' => $this->repository->find(
                $qualitativeEvaluationSession
            ),
        ]);
    }

    public function save(SaveQualitativeEvaluationSessionRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_sessions.saved'),
            'data' => $this->repository->save(
                $request->validated()
            ),
        ]);
    }

    public function close(QualitativeEvaluationSession $qualitativeEvaluationSession): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_sessions.closed'),
            'data' => $this->repository->close(
                $qualitativeEvaluationSession
            ),
        ]);
    }

    public function reopen(QualitativeEvaluationSession $qualitativeEvaluationSession): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.qualitative_evaluation_sessions.reopened'),
            'data' => $this->repository->reopen(
                $qualitativeEvaluationSession
            ),
        ]);
    }
}
