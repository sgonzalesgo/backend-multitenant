<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\SubjectType\StoreSubjectTypeRequest;
use App\Http\Requests\Academic\SubjectType\UpdateSubjectTypeRequest;
use App\Models\Academic\SubjectType;
use App\Repositories\Academic\SubjectTypeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubjectTypeController extends Controller
{
    public function __construct(
        private SubjectTypeRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['tenant_id', 'q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.subject_types.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function active(Request $request): JsonResponse
    {
        $data = $this->repo->active(
            $request->only([
                'tenant_id',
                'q',
                'per_page',
            ])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.subject_types.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreSubjectTypeRequest $request): JsonResponse
    {
        $subjectType = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.subject_types.created'),
            'data' => $subjectType,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(SubjectType $subjectType): JsonResponse
    {
        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.subject_types.shown'),
            'data' => $subjectType,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateSubjectTypeRequest $request, SubjectType $subjectType): JsonResponse
    {
        $subjectType = $this->repo->update(
            $subjectType,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.subject_types.updated'),
            'data' => $subjectType,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(SubjectType $subjectType): JsonResponse
    {
        $this->repo->delete($subjectType);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.subject_types.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
