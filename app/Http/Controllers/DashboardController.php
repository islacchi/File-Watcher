<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ConfigService;
use App\Services\EventService;
use App\Services\SnapshotService;
use App\View\Models\DashboardViewModel;
use App\View\Models\EventViewModel;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
        private readonly SnapshotService $snapshotService,
        private readonly ConfigService $configService,
    ) {}

    /**
     * Display the dashboard.
     */
    public function index(Request $request)
    {
        $todayCount = $this->eventService->getTodayCount();
        $yesterdayCount = $this->eventService->getYesterdayCount();
        $deletionsLast24h = $this->eventService->getDeletedLast24hCount();
        $yesterdayDeletions = $this->eventService->getYesterdayCount();
        $totalTracked = $this->snapshotService->getTotalTracked();

        // Use the config table's started_at if available, otherwise fall back to first event today
        $lastStartupTime = $this->configService->getStartedAt()
            ?? $this->eventService->getLastStartupTime();

        $viewModel = new DashboardViewModel(
            eventsToday: $todayCount,
            deletionsLast24h: $deletionsLast24h,
            totalTrackedFiles: $totalTracked,
            lastStartupTime: $lastStartupTime,
            todayTrend: \App\Services\Formatter::trendPercent($todayCount, $yesterdayCount),
            deletionsTrend: \App\Services\Formatter::trendPercent($deletionsLast24h, $yesterdayDeletions),
            sparkline: $this->eventService->getDailyCountsForWeek(),
            eventTypeCounts: $this->eventService->getEventCountsToday(),
            yesterdayCount: $yesterdayCount,
            yesterdayDeletions: $yesterdayDeletions,
            lastActivityTime: $this->eventService->getLastActivityTime(),
        );

        $recentActivity = $this->eventService->getRecentActivity(10)
            ->map(fn ($event) => EventViewModel::fromModel($event));

        return view('dashboard', compact('viewModel', 'recentActivity'));
    }
}