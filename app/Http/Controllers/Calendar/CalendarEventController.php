<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\Event\ListCalendarEventRequest;
use App\Http\Requests\Calendar\Event\StoreCalendarEventRequest;
use App\Http\Requests\Calendar\Event\UpdateCalendarEventRequest;
use App\Models\Calendar\CalendarEvent;
use App\Repositories\Calendar\CalendarEventRepository;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CalendarEventController extends Controller
{
    public function __construct(
        protected CalendarEventRepository $repository
    ) {
    }

    public function index(ListCalendarEventRequest $request): JsonResponse
    {
        $data = $this->repository->list(
            filters: $request->validated(),
            user: $request->user()
        );

        return response()->json($data, Response::HTTP_OK);
    }

    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $data = $this->repository->store(
            data: $request->validated(),
            user: $request->user()
        );

        return response()->json($data, Response::HTTP_CREATED);
    }

    public function show(CalendarEvent $calendarEvent): JsonResponse
    {
        $data = $this->repository->show(
            calendarEvent: $calendarEvent,
            user: auth()->user()
        );

        return response()->json($data, Response::HTTP_OK);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent): JsonResponse
    {
        $data = $this->repository->update(
            calendarEvent: $calendarEvent,
            data: $request->validated(),
            user: $request->user()
        );

        return response()->json($data, Response::HTTP_OK);
    }

    public function destroy(CalendarEvent $calendarEvent): JsonResponse
    {
        $data = $this->repository->delete(
            calendarEvent: $calendarEvent,
            user: auth()->user()
        );

        return response()->json($data, Response::HTTP_OK);
    }
}
