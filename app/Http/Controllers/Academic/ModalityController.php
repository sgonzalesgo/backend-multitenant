<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\Modality\StoreModalityRequest;
use App\Http\Requests\Academic\Modality\UpdateModalityRequest;
use App\Models\Academic\Modality;
use App\Repositories\Academic\ModalityRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModalityController extends Controller
{
    public function __construct(
        private ModalityRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.modalities.listed'),
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
            'message' => __('messages.modalities.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreModalityRequest $request): JsonResponse
    {
        $modality = $this->repo->create(
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.modalities.created'),
            'data' => $modality,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(Modality $modality): JsonResponse
    {
        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.modalities.shown'),
            'data' => $modality,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdateModalityRequest $request, Modality $modality): JsonResponse
    {
        $modality = $this->repo->update(
            $modality,
            $request->validated()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.modalities.updated'),
            'data' => $modality,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(Modality $modality): JsonResponse
    {
        $this->repo->delete($modality);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.modalities.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
