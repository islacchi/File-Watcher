<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventType;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EventService
{
    /**
     * Get the count of events for today.
     */
    public function getTodayCount(): int
    {
        return (int) Cache::remember('events_today_count', 30, function (): int {
            $today = Carbon::now()->startOfDay()->toIso8601String();

            return Event::where('timestamp', '>=', $today)->count();
        });
    }

    /**
     * Get the count of deletion events in the last 24 hours.
     */
    public function getDeletedLast24hCount(): int
    {
        return (int) Cache::remember('events_deleted_24h', 30, function (): int {
            $since = Carbon::now()->subHours(24)->toIso8601String();

            return Event::where('timestamp', '>=', $since)
                ->where(function ($query): void {
                    $query->where('event_type', 'DELETED')
                        ->orWhere('event_type', 'DELETED (offline)');
                })->count();
        });
    }

    /**
     * Get the timestamp of the most recent event (last activity).
     */
    public function getLastActivityTime(): ?string
    {
        $event = Event::orderByDesc('timestamp')->first();

        return $event?->timestamp;
    }

    /**
     * Get the timestamp of the first event today (proxy for startup scan time).
     */
    public function getLastStartupTime(): ?string
    {
        return (string) Cache::remember('events_last_startup', 30, function (): ?string {
            $today = Carbon::now()->startOfDay()->toIso8601String();

            $event = Event::where('timestamp', '>=', $today)
                ->orderBy('timestamp', 'asc')
                ->first();

            return $event?->timestamp;
        });
    }

    /**
     * Get recent events for the activity feed.
     *
     * @return Collection<int, Event>
     */
    public function getRecentActivity(int $limit = 10): Collection
    {
        return Event::orderByDesc('timestamp')
            ->limit($limit)
            ->get();
    }

    /**
     * Get event counts grouped by event type for today.
     *
     * @return array<string, int>
     */
    public function getEventCountsToday(): array
    {
        return (array) Cache::remember('events_counts_today', 30, function (): array {
            $today = Carbon::now()->startOfDay()->toIso8601String();

            $counts = Event::where('timestamp', '>=', $today)
                ->select('event_type', DB::raw('count(*) as total'))
                ->groupBy('event_type')
                ->pluck('total', 'event_type')
                ->toArray();

            $result = [];
            foreach (EventType::cases() as $type) {
                $live    = $counts[$type->value] ?? 0;
                $offline = $counts[$type->offline()] ?? 0;
                $result[$type->value] = $live + $offline;
            }

            return $result;
        });
    }

    /**
     * Get the last 7 days of daily event counts for sparkline data.
     *
     * @return array<int, array{date: string, count: int}>
     */
    public function getDailyCountsForWeek(): array
    {
        $results = [];
        $now = Carbon::now();

        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->startOfDay();
            $nextDay = $day->copy()->addDay();

            $count = Event::where('timestamp', '>=', $day->toIso8601String())
                ->where('timestamp', '<', $nextDay->toIso8601String())
                ->count();

            $results[] = [
                'date' => $day->format('M j'),
                'count' => $count,
            ];
        }

        return $results;
    }

    /**
     * Get the daily event count for yesterday (for trend comparison).
     */
    public function getYesterdayCount(): int
    {
        $yesterday = Carbon::now()->subDay()->startOfDay()->toIso8601String();
        $today = Carbon::now()->startOfDay()->toIso8601String();

        return Event::where('timestamp', '>=', $yesterday)
            ->where('timestamp', '<', $today)
            ->count();
    }

    /**
     * Get filtered and paginated events.
     */
    public function getFilteredEvents(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = Event::query();

        // Search by filename
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('src_path', 'LIKE', "%{$search}%")
                    ->orWhere('dest_path', 'LIKE', "%{$search}%");
            });
        }

        // Filter by event type
        if (! empty($filters['event_type'])) {
            $types = (array) $filters['event_type'];
            $query->whereIn('event_type', $types);
        }

        // Tab-based quick filter
        if (! empty($filters['tab']) && empty($filters['event_type'])) {
            $tab = $filters['tab'];
            $query->where(function ($q) use ($tab): void {
                match ($tab) {
                    'modified' => $q->where('event_type', 'LIKE', 'MODIFIED%'),
                    'deleted' => $q->where('event_type', 'LIKE', 'DELETED%'),
                    'renamed' => $q->where('event_type', 'LIKE', 'RENAMED%'),
                    'moved' => $q->where(function ($q2): void {
                        $q2->where('event_type', 'LIKE', 'MOVED%')
                            ->where('event_type', 'NOT LIKE', 'MOVED_AND_RENAMED%');
                    }),
                    'offline' => $q->where('event_type', 'LIKE', '%(offline)%'),
                    default => null,
                };
            });
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->where('timestamp', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            // Include the entire day by adding 23:59:59
            $toDate = Carbon::parse($filters['date_to'])->endOfDay()->toIso8601String();
            $query->where('timestamp', '<=', $toDate);
        }

        // Filter by file extension
        if (! empty($filters['extension'])) {
            $ext = $filters['extension'];
            $query->where(function ($q) use ($ext): void {
                $q->where('src_path', 'LIKE', "%.{$ext}")
                    ->orWhere('dest_path', 'LIKE', "%.{$ext}");
            });
        }
        $sortBy = $filters['sort_by'] ?? 'time';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $allowedSorts = ['time' => 'timestamp', 'size' => 'file_size'];
        $sortColumn = $allowedSorts[$sortBy] ?? 'timestamp';

        return $query->orderBy($sortColumn, $sortDir === 'asc' ? 'asc' : 'desc')->paginate($perPage);
    }

    /**
     * Get the complete file timeline for a given path.
     * Follows the file across renames and moves by linking events via md5_hash.
     *
     * @return Collection<int, Event>
     */
    public function getFileTimeline(string $path): Collection
    {
        // Get all events where this path was the source
        $directEvents = Event::where('src_path', $path)
            ->orderByDesc('timestamp')
            ->get();

        // Collect all md5 hashes to follow renames/moves
        $hashes = $directEvents
            ->pluck('md5_hash')
            ->filter()
            ->unique()
            ->toArray();

        // Also collect dest_path hashes from renames/moves to trace backwards
        $destHashes = Event::where('dest_path', $path)
            ->orderByDesc('timestamp')
            ->pluck('md5_hash')
            ->filter()
            ->unique()
            ->toArray();

        $allHashes = array_unique(array_merge($hashes, $destHashes));

        // Find all events associated with these hashes
        $hashEvents = collect();
        if (! empty($allHashes)) {
            $hashEvents = Event::whereIn('md5_hash', $allHashes)
                ->orderByDesc('timestamp')
                ->get();
        }

        // Merge and deduplicate by id
        $allEvents = $directEvents->merge($hashEvents)->unique('id');

        // Sort by timestamp descending
        return $allEvents->sortByDesc('timestamp')->values();
    }

    /**
     * Get all distinct file extensions from events.
     *
     * @return Collection<int, string>
     */
    public function getExtensions(): Collection
    {
        $extensions = Event::select('src_path')
            ->whereNotNull('src_path')
            ->get()
            ->map(function (Event $event): string {
                $ext = pathinfo($event->src_path, PATHINFO_EXTENSION);

                return strtolower($ext);
            })
            ->filter(fn (string $ext): bool => $ext !== '')
            ->unique()
            ->sort()
            ->values();

        return $extensions;
    }

    /**
     * Analytics: event volume by type per day within a date range.
     *
     * @return array<int, array{date: string, CREATED: int, MODIFIED: int, DELETED: int, RENAMED: int, MOVED: int, MOVED_AND_RENAMED: int}>
     */
    public function getAnalyticsDailyByType(string $from, string $to): array
    {
        $rows = Event::select(
                DB::raw("DATE(timestamp) as day"),
                'event_type',
                DB::raw('COUNT(*) as count')
            )
            ->where('timestamp', '>=', $from)
            ->where('timestamp', '<=', $to)
            ->groupBy('day', 'event_type')
            ->orderBy('day', 'asc')
            ->get();

        // Pivot into per-day arrays keyed by date
        $days = [];
        foreach ($rows as $row) {
            $day = $row->day;
            if (! isset($days[$day])) {
                $days[$day] = [
                    'date' => Carbon::parse($day)->format('M j'),
                    'CREATED' => 0,
                    'MODIFIED' => 0,
                    'DELETED' => 0,
                    'RENAMED' => 0,
                    'MOVED' => 0,
                    'MOVED_AND_RENAMED' => 0,
                ];
            }
            // Strip "(offline)" suffix and map to the canonical key
            $cleanType = str_replace(' (offline)', '', $row->event_type);
            if (isset($days[$day][$cleanType])) {
                $days[$day][$cleanType] += (int) $row->count;
            }
        }

                // Fill in zero-count days so the chart always shows the full date range
        $filled = [];
        $current = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        while ($current->lte($end)) {
            $key = $current->toDateString();
            $filled[] = $days[$key] ?? [
                'date' => $current->format('M j'),
                'CREATED' => 0,
                'MODIFIED' => 0,
                'DELETED' => 0,
                'RENAMED' => 0,
                'MOVED' => 0,
                'MOVED_AND_RENAMED' => 0,
            ];
            $current->addDay();
        }

        return $filled;
    }

    /**
     * Analytics: top folders by activity within a date range.
     * Extracts the last two path segments from src_path and strips the common prefix.
     *
     * @return array<int, array{name: string, count: int, pct: float}>
     */
    public function getAnalyticsTopFolders(string $from, string $to, int $limit = 10): array
    {
        $rows = Event::select('src_path', DB::raw('COUNT(*) as count'))
            ->where('timestamp', '>=', $from)
            ->where('timestamp', '<=', $to)
            ->whereNotNull('src_path')
            ->groupBy('src_path')
            ->orderByDesc('count')
            ->limit($limit * 5) // Get more raw paths, then deduplicate after truncation
            ->get();

        // Extract last 2 path segments
        $folderCounts = [];
        foreach ($rows as $row) {
            $segments = preg_split('/[\\\\\/]/', $row->src_path);
            $segments = array_filter($segments, fn ($s) => $s !== '');
            $lastTwo = array_slice($segments, -2);
            $folder = implode('\\', $lastTwo);
            $folderCounts[$folder] = ($folderCounts[$folder] ?? 0) + (int) $row->count;
        }

        // Sort by count descending and limit
        arsort($folderCounts);
        $folderCounts = array_slice($folderCounts, 0, $limit, true);

        // Find common prefix to strip
        $folders = array_keys($folderCounts);
        $commonPrefix = '';
        if (count($folders) > 1) {
            $parts = array_map(fn ($f) => explode('\\', $f), $folders);
            $minLen = min(array_map('count', $parts));
            for ($i = 0; $i < $minLen; $i++) {
                $segment = $parts[0][$i];
                if (str_contains($segment, '...')) {
                    break;
                }
                $allMatch = true;
                foreach ($parts as $p) {
                    if ($p[$i] !== $segment) {
                        $allMatch = false;
                        break;
                    }
                }
                if ($allMatch) {
                    $commonPrefix .= ($commonPrefix ? '\\' : '') . $segment;
                } else {
                    break;
                }
            }
        }

        // Strip common prefix from display names
        $total = array_sum($folderCounts);
        $result = [];
        foreach ($folderCounts as $name => $count) {
            $displayName = $commonPrefix ? preg_replace('/^' . preg_quote($commonPrefix, '/') . '\\\\?/', '', $name) : $name;
            $result[] = [
                'name' => $displayName ?: $name,
                'count' => $count,
                'pct' => $total > 0 ? round($count / $total * 100, 1) : 0,
            ];
        }

        return $result;
    }

    /**
     * Analytics: file extension breakdown within a date range.
     *
     * @return array<int, array{name: string, count: int, pct: float}>
     */
    public function getAnalyticsTopExtensions(string $from, string $to, int $limit = 8): array
    {
        $rows = Event::select(
                DB::raw("LOWER(SUBSTR(src_path, INSTR(src_path, '.')+1)) as ext"),
                DB::raw('COUNT(*) as count')
            )
            ->where('timestamp', '>=', $from)
            ->where('timestamp', '<=', $to)
            ->whereNotNull('src_path')
            ->where('src_path', 'LIKE', '%.%')
            ->groupBy('ext')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        $total = $rows->sum('count');

        return $rows->map(fn ($row) => [
            'name' => $row->ext,
            'count' => (int) $row->count,
            'pct' => $total > 0 ? round($row->count / $total * 100, 1) : 0,
        ])->toArray();
    }

    /**
     * Analytics: file size distribution within a date range.
     * Buckets: <10KB, 10-50KB, 50-200KB, 200KB-1MB, 1-10MB, >10MB
     *
     * @return array{int, int, int, int, int, int}
     */
    public function getAnalyticsSizeDistribution(string $from, string $to): array
    {
        $rows = Event::select(
                DB::raw('
                    CASE
                        WHEN file_size < 10240 THEN 0
                        WHEN file_size < 51200 THEN 1
                        WHEN file_size < 204800 THEN 2
                        WHEN file_size < 1048576 THEN 3
                        WHEN file_size < 10485760 THEN 4
                        ELSE 5
                    END as bucket
                '),
                DB::raw('COUNT(*) as count')
            )
            ->where('timestamp', '>=', $from)
            ->where('timestamp', '<=', $to)
            ->groupBy('bucket')
            ->get();

        $buckets = [0, 0, 0, 0, 0, 0];
        foreach ($rows as $row) {
            $buckets[(int) $row->bucket] = (int) $row->count;
        }

        return $buckets;
    }

    /**
     * Analytics: total count and total file_size within a date range.
     *
     * @return array{total: int, totalSize: int}
     */
    public function getAnalyticsTotals(string $from, string $to): array
    {
        $result = Event::where('timestamp', '>=', $from)
            ->where('timestamp', '<=', $to)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(file_size), 0) as total_size')
            )
            ->first();

        return [
            'total' => (int) $result->total,
            'totalSize' => (int) $result->total_size,
        ];
    }

    /**
     * Analytics: most active event type within a date range.
     *
     * @return array{type: string, count: int, pct: float}
     */
    public function getAnalyticsMostActiveType(string $from, string $to): array
    {
        $row = Event::select('event_type', DB::raw('COUNT(*) as count'))
            ->where('timestamp', '>=', $from)
            ->where('timestamp', '<=', $to)
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->first();

        if (! $row) {
            return ['type' => 'N/A', 'count' => 0, 'pct' => 0];
        }

        $total = Event::where('timestamp', '>=', $from)
            ->where('timestamp', '<=', $to)
            ->count();

        // Strip "(offline)" for display
        $cleanType = str_replace(' (offline)', '', $row->event_type);

        return [
            'type' => $cleanType,
            'count' => (int) $row->count,
            'pct' => $total > 0 ? round($row->count / $total * 100) : 0,
        ];
    }
}
