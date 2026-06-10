<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EventFilterRequest;
use App\Models\Event;
use App\Services\EventService;
use App\View\Models\EventViewModel;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    /**
     * Return the latest event's ID and timestamp for change-detection polling.
     */
    public function latestEvent(): JsonResponse
    {
        $latest = Event::orderByDesc('id')->first();

        if ($latest === null) {
            return response()->json(['id' => 0, 'timestamp' => null]);
        }

        return response()->json([
            'id' => $latest->id,
            'timestamp' => $latest->timestamp,
        ]);
    }

    /**
     * Display the events log.
     */
    public function index(EventFilterRequest $request)
    {
        $filters = $request->validated();

        $events = $this->eventService->getFilteredEvents($filters, perPage: 50);

        $eventViewModels = $events->getCollection()->map(
            fn ($event) => EventViewModel::fromModel($event),
        );

        $extensions = $this->eventService->getExtensions();

        return view('events.index', [
            'events' => $events->setCollection($eventViewModels),
            'filters' => $filters,
            'extensions' => $extensions,
            'eventTypeOptions' => \App\Enums\EventType::options(),
        ]);
    }
}