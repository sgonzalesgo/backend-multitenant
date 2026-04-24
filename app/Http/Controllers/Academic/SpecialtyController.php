<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Specialty\StoreSpecialtyRequest;
use App\Http\Requests\Academic\Specialty\UpdateSpecialtyRequest;
use App\Models\Academic\Specialty;
use App\Repositories\Academic\SpecialtyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SpecialtyController extends Controller
{
    public function __construct(
        private SpecialtyRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.specialties.listed'),
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
            'message' => __('messages.specialties.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreSpecialtyRequest $request): JsonResponse
    {
        $specialty = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.specialties.created'),
            'data' => $specialty,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(Specialty $specialty): JsonResponse
    {
        $specialty = $this->repo->findOrFail($specialty);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.specialties.shown'),
            'data' => $specialty,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateSpecialtyRequest $request, Specialty $specialty): JsonResponse
    {
        $specialty = $this->repo->update(
            $specialty,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.specialties.updated'),
            'data' => $specialty,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(Specialty $specialty): JsonResponse
    {
        $this->repo->delete($specialty);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.specialties.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
