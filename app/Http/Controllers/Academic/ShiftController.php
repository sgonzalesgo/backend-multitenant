<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Shift\StoreShiftRequest;
use App\Http\Requests\Academic\Shift\UpdateShiftRequest;
use App\Models\Academic\Shift;
use App\Repositories\Academic\ShiftRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShiftController extends Controller
{
    public function __construct(
        private ShiftRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.shifts.listed'),
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
            'message' => __('messages.shifts.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $shift = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.shifts.created'),
            'data' => $shift,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(Shift $shift): JsonResponse
    {
        $shift = $this->repo->findOrFail($shift);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.shifts.shown'),
            'data' => $shift,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateShiftRequest $request, Shift $shift): JsonResponse
    {
        $shift = $this->repo->update(
            $shift,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.shifts.updated'),
            'data' => $shift,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        $this->repo->delete($shift);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.shifts.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
