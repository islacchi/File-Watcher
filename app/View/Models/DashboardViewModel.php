<?php

declare(strict_types=1);

namespace App\View\Models;

use App\Services\Formatter;

class DashboardViewModel
{
    /**
     * @param array{value: string, direction: string} $todayTrend
     * @param array{value: string, direction: string} $deletionsTrend
     * @param array<int, array{date: string, count: int}> $sparkline
     * @param array<string, int> $eventTypeCounts
     * @param Collection<int, EventViewModel> $recentActivity
     */
    public function __construct(
        public readonly int $eventsToday,
        public readonly int $deletionsLast24h,
        public readonly int $totalTrackedFiles,
        public readonly ?string $lastStartupTime,
        public readonly array $todayTrend,
        public readonly array $deletionsTrend,
        public readonly array $sparkline,
        public readonly array $eventTypeCounts,
        public readonly int $yesterdayCount,
        public readonly int $yesterdayDeletions,
        public readonly ?string $lastActivityTime,
    ) {}

    /**
     * Get the maximum event count for chart scaling.
     */
    public function maxEventCount(): int
    {
        $max = max(array_values($this->eventTypeCounts));

        return max($max, 1);
    }

    /**
     * Get the maximum daily count for sparkline scaling.
     */
    public function maxDailyCount(): int
    {
        $counts = array_column($this->sparkline, 'count');
        $max = max($counts);

        return max($max, 1);
    }

    /**
     * Get relative time for last startup.
     */
    public function relativeStartupTime(): string
    {
        return Formatter::relativeTime($this->lastStartupTime);
    }

    /**
     * Get relative time for last activity.
     */
    public function relativeActivityTime(): string
    {
        return Formatter::relativeTime($this->lastActivityTime);
    }
}