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

            // Ensure all event types are represented
            $result = [];
            foreach (EventType::cases() as $type) {
                $result[$type->value] = $counts[$type->value] ?? 0;
                $offlineKey = $type->offline();
                if (isset($counts[$offlineKey])) {
                    $result[$type->value . '_offline'] = $counts[$offlineKey];
                }
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
}