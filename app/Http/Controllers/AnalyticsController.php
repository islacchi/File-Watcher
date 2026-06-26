<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EventService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    public function index(Request $request): View
    {
        $now = Carbon::now();

        // Date ranges for each pill (4 ranges computed server-side)
        $ranges = [
            '7d'   => $now->copy()->subDays(6)->startOfDay()->toIso8601String(),
            '30d'  => $now->copy()->subDays(29)->startOfDay()->toIso8601String(),
            '90d'  => $now->copy()->subDays(89)->startOfDay()->toIso8601String(),
            '365d' => $now->copy()->subDays(364)->startOfDay()->toIso8601String(),
        ];
        $to = $now->toIso8601String();

        // Compute data for each range
        $dailyByType = [];
        $topFolders = [];
        $topExtensions = [];
        $sizeDistribution = [];
        $summaryCards = [];

        $daysMap = ['7d' => 7, '30d' => 30, '90d' => 90, '365d' => 365];

        foreach ($ranges as $key => $from) {
            $cached = Cache::remember("analytics_{$key}", 60, function () use ($key, $from, $to, $daysMap): array {
                $daily = $key === '365d'
                    ? $this->eventService->getAnalyticsWeeklyByType($from, $to)
                    : $this->eventService->getAnalyticsDailyByType($from, $to);

                $totals    = $this->eventService->getAnalyticsTotals($from, $to);
                $mostActive = $this->eventService->getAnalyticsMostActiveType($from, $to);
                $daysCount = $daysMap[$key] ?? 1;

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

            $dailyByType[$key]      = $cached['daily'];
            $topFolders[$key]       = $cached['folders'];
            $topExtensions[$key]    = $cached['extensions'];
            $sizeDistribution[$key] = $cached['sizes'];
            $summaryCards[$key]     = $cached['summary'];
        }

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