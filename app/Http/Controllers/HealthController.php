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
     * Primary check: reads the "status" key from the config table.
     * The Python script writes "live" on startup and "offline" on shutdown.
     *
     * Fallback 1: checks the heartbeat timestamp (within 12 seconds).
     * Fallback 2: checks the last event timestamp (within 60 seconds).
     *
     * Includes config metadata from the Python script for the frontend.
     */
    public function check(): JsonResponse
    {
        // Primary: check explicit status from the Python script
        $explicitStatus = $this->configService->getStatus();

        if ($explicitStatus !== null) {
            $isOnline = $explicitStatus === 'live';
        } else {
            // Fallback 1: check heartbeat (script writes every 5s, threshold 12s allows 1 missed cycle + buffer)
            $heartbeat = $this->configService->getFresh('heartbeat');

            if ($heartbeat !== null) {
                $isOnline = Carbon::parse($heartbeat)->diffInSeconds(Carbon::now()) <= 12;
            } else {
                // Fallback 2: check last event timestamp for older script versions
                $lastEvent = Event::orderByDesc('timestamp')->first();

                $isOnline = $lastEvent !== null
                    && Carbon::parse($lastEvent->timestamp)->diffInSeconds(Carbon::now()) <= 60;
            }
        }

        return response()->json([
            'status' => $isOnline ? 'online' : 'offline',
            'heartbeat' => $this->configService->getFresh('heartbeat'),
            'watch_directory' => $this->configService->getWatchDirectory(),
            'script_version' => $this->configService->getScriptVersion(),
            'started_at' => $this->configService->getStartedAt(),
        ]);
    }
}
