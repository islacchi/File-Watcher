<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EventFilterRequest;
use App\Models\Event;
use App\Services\EventService;
use App\View\Models\EventViewModel;
use App\Enums\EventType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
     * Return today's event counts by type for real-time dashboard updates.
     */
    public function todayCounts(): JsonResponse
    {
        $today = Carbon::now()->startOfDay()->toIso8601String();

        $counts = Event::where('timestamp', '>=', $today)
            ->select('event_type', DB::raw('count(*) as total'))
            ->groupBy('event_type')
            ->pluck('total', 'event_type')
            ->toArray();

        // Ensure all event types are represented with at least 0
        $result = [];
        foreach (EventType::cases() as $type) {
            $result[$type->value] = ($counts[$type->value] ?? 0) + ($counts[$type->offline()] ?? 0);
        }

        return response()->json($result);
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