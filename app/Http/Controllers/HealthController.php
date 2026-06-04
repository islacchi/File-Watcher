<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Health check endpoint that returns online/offline status.
     * Online if events table was updated in the last 60 seconds.
     */
    public function check(): JsonResponse
    {
        $lastEvent = Event::orderByDesc('timestamp')->first();

        if ($lastEvent === null) {
            return response()->json(['status' => 'offline']);
        }

        $lastTimestamp = Carbon::parse($lastEvent->timestamp);
        $isOnline = $lastTimestamp->diffInSeconds(Carbon::now()) <= 60;

        return response()->json([
            'status' => $isOnline ? 'online' : 'offline',
            'last_event' => $lastEvent->timestamp,
        ]);
    }
}