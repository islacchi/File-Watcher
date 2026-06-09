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
     *
     * Primary check: reads the heartbeat timestamp from the config table.
     * The Python script upserts this every 30 seconds while running.
     * We consider the script online if the heartbeat is within 90 seconds.
     *
     * Fallback: checks the last event timestamp (within 60 seconds)
     * for backwards compatibility before the heartbeat was introduced.
     *
     * Includes config metadata from the Python script for the frontend.
     */
    public function check(): JsonResponse
    {
        $heartbeat = $this->configService->getFresh('heartbeat');
        $isOnline = false;

        if ($heartbeat !== null) {
            // Primary: check heartbeat (script writes every 5s, threshold 12s allows 1 missed cycle + buffer)
            $isOnline = Carbon::parse($heartbeat)->diffInSeconds(Carbon::now()) <= 12;
        } else {
            // Fallback: check last event timestamp for older script versions
            $lastEvent = Event::orderByDesc('timestamp')->first();

            if ($lastEvent !== null) {
                $isOnline = Carbon::parse($lastEvent->timestamp)->diffInSeconds(Carbon::now()) <= 60;
            }
        }

        return response()->json([
            'status' => $isOnline ? 'online' : 'offline',
            'heartbeat' => $heartbeat,
            'watch_directory' => $this->configService->getWatchDirectory(),
            'script_version' => $this->configService->getScriptVersion(),
            'started_at' => $this->configService->getStartedAt(),
        ]);
    }
}
