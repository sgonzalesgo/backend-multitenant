<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\LegalRepresentative\StoreLegalRepresentativeRequest;
use App\Http\Requests\Academic\LegalRepresentative\UpdateLegalRepresentativeRequest;
use App\Models\Academic\LegalRepresentative;
use App\Repositories\Academic\LegalRepresentativeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegalRepresentativeController extends Controller
{
    public function __construct(
        protected LegalRepresentativeRepository $repo
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.legal_representatives.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function active(Request $request): JsonResponse
    {
        $data = $this->repo->active(
            $request->only(['per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.legal_representatives.active_listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StoreLegalRepresentativeRequest $request): JsonResponse
    {
        $legalRepresentative = $this->repo->create(
            $request->validated(),
            $request->file('photo')
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.legal_representatives.created'),
            'data' => $legalRepresentative,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(LegalRepresentative $legalRepresentative): JsonResponse
    {
        $legalRepresentative = $this->repo->find($legalRepresentative);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.legal_representatives.shown'),
            'data' => $legalRepresentative,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(
        UpdateLegalRepresentativeRequest $request,
        LegalRepresentative $legalRepresentative
    ): JsonResponse {
        $legalRepresentative = $this->repo->update(
            $legalRepresentative,
            $request->validated(),
            $request->file('photo')
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.legal_representatives.updated'),
            'data' => $legalRepresentative,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(LegalRepresentative $legalRepresentative): JsonResponse
    {
        $this->repo->delete($legalRepresentative);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.legal_representatives.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
