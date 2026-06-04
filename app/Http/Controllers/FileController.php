<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FileTimelineRequest;
use App\Services\EventService;
use App\View\Models\EventViewModel;
use Carbon\Carbon;

class FileController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    /**
     * Display the file timeline.
     */
    public function timeline(FileTimelineRequest $request)
    {
        $path = $request->validated('path');

        $events = $this->eventService->getFileTimeline($path);

        $eventViewModels = $events->map(
            fn ($event) => EventViewModel::fromModel($event),
        );

        $sessions = $this->groupIntoSessions($eventViewModels);

        return view('files.timeline', [
            'path' => $path,
            'events' => $eventViewModels,
            'sessions' => $sessions,
            'totalEvents' => $eventViewModels->count(),
        ]);
    }

    /**
     * Group events into sessions (events within 5 minutes of each other).
     *
     * @param \Illuminate\Support\Collection<int, EventViewModel> $events
     * @return array<int, array{start: string, end: string, events: \Illuminate\Support\Collection}>
     */
    private function groupIntoSessions(\Illuminate\Support\Collection $events): array
    {
        if ($events->isEmpty()) {
            return [];
        }

        $sessions = [];
        $currentSession = [
            'start' => $events->first()->timestamp,
            'end' => $events->first()->timestamp,
            'events' => collect([$events->first()]),
        ];

        $previousTime = Carbon::parse($events->first()->timestamp);

        for ($i = 1; $i < $events->count(); $i++) {
            $event = $events[$i];
            $currentTime = Carbon::parse($event->timestamp);

            if ($previousTime->diffInMinutes($currentTime) <= 5) {
                $currentSession['events']->push($event);
                $currentSession['start'] = $event->timestamp;
            } else {
                $sessions[] = $currentSession;
                $currentSession = [
                    'start' => $event->timestamp,
                    'end' => $event->timestamp,
                    'events' => collect([$event]),
                ];
            }

            $previousTime = $currentTime;
        }

        $sessions[] = $currentSession;

        return $sessions;
    }
}