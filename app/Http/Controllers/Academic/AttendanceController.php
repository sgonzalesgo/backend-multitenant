<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Attendance\AttendanceDaysRequest;
use App\Http\Requests\Academic\Attendance\OpenAttendanceDayRequest;
use App\Http\Requests\Academic\Attendance\SaveAttendanceSessionRequest;
use App\Models\Academic\AttendanceSession;
use App\Repositories\Academic\AttendanceRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Academic\Attendance\AttendanceRecordsRequest;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceRepository $repo) {}

    public function mySubjects(Request $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance.subjects_retrieved'),
            'data' => $this->repo->mySubjects($request->only(['academic_year_id'])),
            'error' => null,
        ]);
    }

    public function days(AttendanceDaysRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance.days_retrieved'),
            'data' => $this->repo->days($request->validated()),
            'error' => null,
        ]);
    }

    public function openDay(OpenAttendanceDayRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance.session_opened'),
            'data' => $this->repo->openDay($request->validated()),
            'error' => null,
        ]);
    }

    public function save(
        SaveAttendanceSessionRequest $request,
        AttendanceSession $attendanceSession
    ): JsonResponse {
        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance.session_saved'),
            'data' => $this->repo->save($attendanceSession, $request->validated()),
            'error' => null,
        ]);
    }

    public function records(AttendanceRecordsRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance.records_retrieved'),
            'data' => $this->repo->records($request->validated()),
            'error' => null,
        ]);
    }

    public function reopen(AttendanceSession $attendanceSession): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => __('messages.attendance.session_reopened'),
            'data' => $this->repo->reopen($attendanceSession),
            'error' => null,
        ]);
    }
}
