<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EventService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AnalyticsController extends Controller
{
    /**
     * Whitelist of valid range keys. Never trust the {key} route param
     * directly into date math or cache key construction.
     */
    private const RANGES = ['7d', '30d', '90d', '365d'];

    private const DAYS_MAP = ['7d' => 7, '30d' => 30, '90d' => 90, '365d' => 365];

    public function __construct(
        private readonly EventService $eventService,
    ) {}

    public function index(Request $request): View
    {
        // Only compute the default range on initial page load.
        // The other three are fetched lazily via range() when the user
        // actually clicks that pill — see resources/views/analytics/index.blade.php
        $defaultRange = '7d';
        $data = $this->computeRangeData($defaultRange);

        $dailyByType      = [$defaultRange => $data['daily']];
        $topFolders       = [$defaultRange => $data['folders']];
        $topExtensions    = [$defaultRange => $data['extensions']];
        $sizeDistribution = [$defaultRange => $data['sizes']];
        $summaryCards     = [$defaultRange => $data['summary']];

        // Static metadata
        $eventTypes = ['CREATED', 'MODIFIED', 'DELETED', 'RENAMED', 'MOVED', 'MOVED_AND_RENAMED'];
        $typeLabels = [
            'CREATED' => 'Created',
            'MODIFIED' => 'Modified',
            'DELETED' => 'Deleted',
            'RENAMED' => 'Renamed',
            'MOVED' => 'Moved',
            'MOVED_AND_RENAMED' => 'Moved & Renamed',
        ];
        $typeColors = [
            'CREATED' => 'bg-green-500',
            'MODIFIED' => 'bg-blue-500',
            'DELETED' => 'bg-red-500',
            'RENAMED' => 'bg-yellow-500',
            'MOVED' => 'bg-teal-400',
            'MOVED_AND_RENAMED' => 'bg-indigo-400',
        ];
        $sizeBuckets = ['<10 KB', '10–50 KB', '50–200 KB', '200 KB–1 MB', '1–10 MB', '>10 MB'];

        return view('analytics.index', compact(
            'eventTypes',
            'typeLabels',
            'typeColors',
            'sizeBuckets',
            'dailyByType',
            'topFolders',
            'topExtensions',
            'sizeDistribution',
            'summaryCards',
        ));
    }

    /**
     * JSON endpoint for lazily fetching a single range's analytics data.
     * Called from Alpine when the user clicks a range pill that hasn't
     * been loaded yet.
     */
    public function range(string $key): JsonResponse
    {
        if (! in_array($key, self::RANGES, true)) {
            throw new NotFoundHttpException("Unknown analytics range: {$key}");
        }

        return response()->json($this->computeRangeData($key));
    }

    /**
     * Compute (or pull from cache) the full analytics payload for a
     * single range key. This is the unit of work that used to run
     * 4x on every page load — now it runs once per range, only when
     * that range is actually requested.
     *
     * @return array{daily: array, folders: array, extensions: array, sizes: array, summary: array}
     */
    private function computeRangeData(string $key): array
    {
        $now = Carbon::now();
        $to = $now->toIso8601String();

        $daysBack = self::DAYS_MAP[$key] - 1;
        $from = $now->copy()->subDays($daysBack)->startOfDay()->toIso8601String();

        return Cache::remember("analytics_{$key}", 60, function () use ($key, $from, $to): array {
            $daily = $key === '365d'
                ? $this->eventService->getAnalyticsWeeklyByType($from, $to)
                : $this->eventService->getAnalyticsDailyByType($from, $to);

            $totals     = $this->eventService->getAnalyticsTotals($from, $to);
            $mostActive = $this->eventService->getAnalyticsMostActiveType($from, $to);
            $daysCount  = self::DAYS_MAP[$key];

            return [
                'daily'      => $daily,
                'folders'    => $this->eventService->getAnalyticsTopFolders($from, $to),
                'extensions' => $this->eventService->getAnalyticsTopExtensions($from, $to),
                'sizes'      => $this->eventService->getAnalyticsSizeDistribution($from, $to),
                'summary'    => [
                    'total'      => $totals['total'],
                    'avg'        => round($totals['total'] / $daysCount),
                    'mostActive' => $mostActive['type'],
                    'pct'        => $mostActive['pct'],
                    'data'       => $this->formatBytes($totals['totalSize']),
                ],
            ];
        });
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_073_741_824) {
            return round($bytes / 1_073_741_824, 1) . ' GB';
        }
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}