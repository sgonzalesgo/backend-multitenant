<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\EventType\ListCalendarEventTypeRequest;
use App\Http\Requests\Calendar\EventType\StoreCalendarEventTypeRequest;
use App\Http\Requests\Calendar\EventType\UpdateCalendarEventTypeRequest;
use App\Models\Calendar\CalendarEventType;
use App\Repositories\Calendar\CalendarEventTypeRepository;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CalendarEventTypeController extends Controller
{
    public function __construct(
        protected CalendarEventTypeRepository $repository
    ) {
    }

    public function index(ListCalendarEventTypeRequest $request): JsonResponse
    {
        $data = $this->repository->list($request->validated());

        return response()->json($data, Response::HTTP_OK);
    }

    public function store(StoreCalendarEventTypeRequest $request): JsonResponse
    {
        $data = $this->repository->store($request->validated(), $request->user());

        return response()->json($data, Response::HTTP_CREATED);
    }

    public function show(CalendarEventType $calendarEventType): JsonResponse
    {
        $data = $this->repository->show($calendarEventType);

        return response()->json($data, Response::HTTP_OK);
    }

    public function update(UpdateCalendarEventTypeRequest $request, CalendarEventType $calendarEventType): JsonResponse
    {
        $data = $this->repository->update($calendarEventType, $request->validated(), $request->user());

        return response()->json($data, Response::HTTP_OK);
    }

    public function destroy(CalendarEventType $calendarEventType): JsonResponse
    {
        $data = $this->repository->delete($calendarEventType, auth()->user());

        return response()->json($data, Response::HTTP_OK);
    }
}
