<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AttendanceJustification\AttendanceJustificationIndexRequest;
use App\Http\Requests\Academic\AttendanceJustification\ReviewAttendanceJustificationRequest;
use App\Http\Requests\Academic\AttendanceJustification\StoreAttendanceJustificationRequest;
use App\Http\Requests\Academic\AttendanceJustification\AttendanceJustificationPendingRecordsRequest;
use App\Models\Academic\AttendanceJustification;
use App\Repositories\Academic\AttendanceJustificationRepository;
use App\Http\Requests\Academic\AttendanceJustification\UploadAttendanceJustificationDocumentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AttendanceJustificationController extends Controller
{
    public function __construct(
        protected AttendanceJustificationRepository $repository
    ) {}

    public function index(AttendanceJustificationIndexRequest $request): JsonResponse
    {
        $data = $this->repository->index($request->validated());

        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance_justification.list_success'),
            'data' => $data,
            'error' => null,
        ]);
    }

    public function store(StoreAttendanceJustificationRequest $request): JsonResponse
    {
        $justification = $this->repository->store($request->validated());

        return response()->json([
            'code' => 201,
            'message' => __('messages.attendance_justification.created_success'),
            'data' => $justification,
            'error' => null,
        ], 201);
    }

    public function approve(
        ReviewAttendanceJustificationRequest $request,
        AttendanceJustification $attendanceJustification
    ): JsonResponse {
        $justification = $this->repository->approve(
            $attendanceJustification,
            $request->validated()
        );

        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance_justification.approved_success'),
            'data' => $justification,
            'error' => null,
        ]);
    }

    public function reject(
        ReviewAttendanceJustificationRequest $request,
        AttendanceJustification $attendanceJustification
    ): JsonResponse {
        $justification = $this->repository->reject(
            $attendanceJustification,
            $request->validated()
        );

        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance_justification.rejected_success'),
            'data' => $justification,
            'error' => null,
        ]);
    }

    public function pendingRecords(AttendanceJustificationPendingRecordsRequest $request): JsonResponse
    {
        $data = $this->repository->pendingRecords($request->validated());

        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance_justification.pending_records_success'),
            'data' => $data,
            'error' => null,
        ]);
    }

    public function destroy(AttendanceJustification $attendanceJustification): JsonResponse
    {
        $this->repository->delete($attendanceJustification);

        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance_justification.deleted_success'),
            'data' => null,
            'error' => null,
        ]);
    }

    /**
     * @throws \Throwable
     * @throws ValidationException
     */
    public function uploadDocument(
        UploadAttendanceJustificationDocumentRequest $request,
        AttendanceJustification $attendanceJustification
    ): JsonResponse {
        $justification = $this->repository->uploadDocument(
            $attendanceJustification,
            $request->validated()
        );

        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance_justification.document_uploaded_success'),
            'data' => $justification,
            'error' => null,
        ]);
    }
}
