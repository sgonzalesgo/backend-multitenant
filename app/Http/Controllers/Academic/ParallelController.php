<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Parallel\StoreParallelRequest;
use App\Http\Requests\Academic\Parallel\UpdateParallelRequest;
use App\Models\Academic\Parallel;
use App\Repositories\Academic\ParallelRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParallelController extends Controller
{
    public function __construct(
        private ParallelRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.parallels.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function active(Request $request): JsonResponse
    {
        $data = $this->repo->active(
            $request->only(['sort', 'dir'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.parallels.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreParallelRequest $request): JsonResponse
    {
        $parallel = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.parallels.created'),
            'data' => $parallel,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(Parallel $parallel): JsonResponse
    {
        $parallel = $this->repo->findOrFail($parallel);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.parallels.shown'),
            'data' => $parallel,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateParallelRequest $request, Parallel $parallel): JsonResponse
    {
        $parallel = $this->repo->update(
            $parallel,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.parallels.updated'),
            'data' => $parallel,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(Parallel $parallel): JsonResponse
    {
        $this->repo->delete($parallel);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.parallels.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
