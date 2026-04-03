<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Http\Requests\General\Person\LookupPersonByLegalIdRequest;
use App\Http\Requests\General\Person\StorePersonRequest;
use App\Http\Requests\General\Person\UpdatePersonRequest;
use App\Models\General\Person;
use App\Repositories\General\PersonRepository;
use App\Services\General\PersonLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PersonController extends Controller
{
    public function __construct(
        protected PersonRepository $repo,
        protected PersonLookupService $lookupService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->repo->list(
            $request->only(['q', 'sort', 'dir', 'per_page'])
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.persons.listed'),
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function store(StorePersonRequest $request): JsonResponse
    {
        $person = $this->repo->create(
            $request->validated(),
            $request->file('photo')
        );

        return response()->json([
            'code' => Response::HTTP_CREATED,
            'message' => __('messages.persons.created'),
            'data' => $person,
            'error' => null,
        ], Response::HTTP_CREATED);
    }

    public function show(Person $person): JsonResponse
    {
        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.persons.shown'),
            'data' => $person,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function update(UpdatePersonRequest $request, Person $person): JsonResponse
    {
        $person = $this->repo->update(
            $person,
            $request->validated(),
            $request->file('photo')
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.persons.updated'),
            'data' => $person,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function destroy(Person $person): JsonResponse
    {
        $this->repo->delete($person);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => __('messages.persons.deleted'),
            'data' => null,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function lookupByLegalId(LookupPersonByLegalIdRequest $request): JsonResponse
    {
        $result = $this->lookupService->lookup(
            $request->string('legal_id')->toString()
        );

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => $result['person']
                ? __('messages.persons.lookup_found')
                : __('messages.persons.lookup_not_found'),
            'data' => $result,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
