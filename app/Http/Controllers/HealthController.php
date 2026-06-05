<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\ConfigService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(
        private readonly ConfigService $configService,
    ) {}

    /**
     * Health check endpoint that returns online/offline status.
     * Online if events table was updated in the last 60 seconds.
     * Includes config metadata from the Python script for the frontend.
     */
    public function check(): JsonResponse
    {
        $lastEvent = Event::orderByDesc('timestamp')->first();

        if ($lastEvent === null) {
            return response()->json([
                'status' => 'offline',
                'watch_directory' => $this->configService->getWatchDirectory(),
                'script_version' => $this->configService->getScriptVersion(),
                'started_at' => $this->configService->getStartedAt(),
            ]);
        }

        $lastTimestamp = Carbon::parse($lastEvent->timestamp);
        $isOnline = $lastTimestamp->diffInSeconds(Carbon::now()) <= 60;

        return response()->json([
            'status' => $isOnline ? 'online' : 'offline',
            'last_event' => $lastEvent->timestamp,
            'watch_directory' => $this->configService->getWatchDirectory(),
            'script_version' => $this->configService->getScriptVersion(),
            'started_at' => $this->configService->getStartedAt(),
        ]);
    }
}
