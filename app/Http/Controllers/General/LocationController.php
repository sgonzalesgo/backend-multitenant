<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\City;
use App\Models\General\Country;
use App\Models\General\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocationController extends Controller
{
    public function countries(): JsonResponse
    {
        $data = Country::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => 'Countries listed successfully.',
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function states(Request $request): JsonResponse
    {
        $countryId = (int) $request->integer('country_id');

        $data = State::query()
            ->when($countryId > 0, fn ($query) => $query->where('country_id', $countryId))
            ->orderBy('name')
            ->get(['id', 'country_id', 'code', 'name']);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => 'States listed successfully.',
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }

    public function cities(Request $request): JsonResponse
    {
        $stateId = (int) $request->integer('state_id');

        $data = City::query()
            ->when($stateId > 0, fn ($query) => $query->where('state_id', $stateId))
            ->orderBy('name')
            ->get(['id', 'state_id', 'name']);

        return response()->json([
            'code' => Response::HTTP_OK,
            'message' => 'Cities listed successfully.',
            'data' => $data,
            'error' => null,
        ], Response::HTTP_OK);
    }
}
