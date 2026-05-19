<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AcademicContext\AcademicContextFilterRequest;
use App\Repositories\Academic\AcademicContextRepository;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AcademicContextController extends Controller
{
    public function __construct(
        protected AcademicContextRepository $repo
    ) {}

    public function resolve(AcademicContextFilterRequest $request): JsonResponse
    {
        $data = $this->repo->resolve(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.academic_context.resolved'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
