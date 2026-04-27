<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Subject\StoreSubjectRequest;
use App\Http\Requests\Academic\Subject\UpdateSubjectRequest;
use App\Models\Academic\Subject;
use App\Repositories\Academic\SubjectRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubjectController extends Controller
{
    public function __construct(
        private SubjectRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['tenant_id', 'q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.subjects.listed'),
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
                'subject_type_id',
                'per_page',
            ])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.subjects.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.subjects.created'),
            'data' => $subject->load('subjectType:id,code,name'),
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(Subject $subject): JsonResponse
    {
        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.subjects.shown'),
            'data' => $subject->load('subjectType:id,code,name'),
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $subject = $this->repo->update(
            $subject,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.subjects.updated'),
            'data' => $subject,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        $this->repo->delete($subject);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.subjects.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
