<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Academic\Student;
use App\Repositories\Dashboard\StudentDashboardRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function __construct(
        protected StudentDashboardRepository $repository
    ) {
    }

    public function show(Request $request, Student $student): JsonResponse
    {
        $data = $this->repository->show($student, [
            'academic_year_id' => $request->input('academic_year_id') ?? $request->query('academic_year_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.students.dashboard_retrieved_successfully'),
            'data' => $data,
        ]);
    }

    public function myDashboard(Request $request): JsonResponse
    {
        $student = $this->repository->findAuthenticatedStudent();

        $data = $this->repository->show($student, [
            'academic_year_id' => $request->input('academic_year_id') ?? $request->query('academic_year_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.students.dashboard_retrieved_successfully'),
            'data' => $data,
        ]);
    }
}
