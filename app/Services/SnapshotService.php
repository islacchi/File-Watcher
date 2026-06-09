<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Snapshot;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SnapshotService
{
    public function __construct(
        private readonly ConfigService $configService,
    ) {}
    /**
     * Get the total number of tracked files.
     */
    public function getTotalTracked(): int
    {
        return (int) Cache::remember('snapshots_total_tracked', 30, function (): int {
            return Snapshot::count();
        });
    }

    /**
     * Get filtered and paginated snapshots.
     */
    public function getFilteredSnapshots(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = Snapshot::query();

        // Search by path
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('path', 'LIKE', "%{$search}%");
        }

        // Filter by file extension
        if (! empty($filters['extension'])) {
            $ext = $filters['extension'];
            $query->where('path', 'LIKE', "%.{$ext}");
        }

        // Filter by directory
        if (! empty($filters['directory'])) {
            $directory = rtrim($filters['directory'], '\\/');
            $query->where('path', 'LIKE', $directory . '%');
        }

        // Filter by exact file path (single-file view)
        if (! empty($filters['path'])) {
            $query->where('path', '=', $filters['path']);
        }

        return $query->orderByDesc('last_seen')->paginate($perPage);
    }

    /**
     * Get a single snapshot by its exact file path.
     * Used by single-file view when clicking a file in the tree.
     */
    public function getSnapshotByPath(string $path): ?Snapshot
    {
        return Snapshot::where('path', $path)->first();
    }

    /**
     * Build a nested directory tree from snapshot paths.
     * Builds to full depth of any directory hierarchy.
     *
     * @return array<int, array{path: string, name: string, children: array, file_count: int}>
     */
    public function getDirectoryTree(): array
    {
        return Cache::remember('snapshots_directory_tree', 120, fn (): array => $this->buildDirectoryTree());
    }

    /**
     * Build the directory tree without caching (for real-time AJAX polling).
     */
    public function getDirectoryTreeFresh(): array
    {
        return $this->buildDirectoryTree();
    }

    /**
     * Core directory tree building logic.
     */
    private function buildDirectoryTree(): array
    {
        $paths = Snapshot::select('path')->pluck('path')->toArray();

        // Strip the watch directory prefix so the tree starts from the watch directory level
        $watchDir = rtrim($this->configService->getWatchDirectory(), '\\/');
        $watchDirParts = array_values(array_filter(explode('\\', $watchDir)));
        $watchDepth = count($watchDirParts);

        // Detect UNC prefix (e.g., \\Kyle\ → UNC) — paths in the DB use UNC format
        $uncPrefix = str_starts_with($watchDir, '\\\\') ? '\\\\' : '';

        $tree = [];

        foreach ($paths as $path) {
            $parts = array_values(array_filter(explode('\\', $path)));

            if (count($parts) <= $watchDepth) {
                continue;
            }

            $relativeParts = array_slice($parts, $watchDepth);
            // No depth limit — build the full tree to any depth
            $maxLevel = count($relativeParts);
            $current = &$tree;

            for ($i = 0; $i < $maxLevel; $i++) {
                $part = $relativeParts[$i];

                if (! isset($current[$part])) {
                    $fullParts = array_merge($watchDirParts, array_slice($relativeParts, 0, $i + 1));
                    // Reconstruct the full path with the UNC prefix for correct LIKE queries
                    $currentPath = $uncPrefix . implode('\\', $fullParts);
                    $current[$part] = [
                        'path' => $currentPath,
                        'name' => $part,
                        'children' => [],
                        'file_count' => 0,
                    ];
                }

                if ($i === count($relativeParts) - 1) {
                    $current[$part]['file_count']++;
                }

                $current = &$current[$part]['children'];
            }
        }
        unset($current);

        return $this->buildTreeArray($tree);
    }

    /**
     * Get files in a specific directory.
     */
    public function getFilesInDirectory(string $directory): Collection
    {
        $directory = rtrim($directory, '\\/');

        return Snapshot::where('path', 'LIKE', $directory . '\\%')
            ->orderBy('path')
            ->get()
            ->filter(function (Snapshot $snapshot) use ($directory): bool {
                // Only include direct children (files in this directory, not subdirectories)
                $relativePath = substr($snapshot->path, strlen($directory) + 1);

                return ! str_contains($relativePath, '\\') && ! str_contains($relativePath, '/');
            })
            ->values();
    }

    /**
     * Check if a snapshot is stale (not seen in more than 7 days).
     */
    public static function isStale(Snapshot $snapshot): bool
    {
        $lastSeen = Carbon::parse($snapshot->last_seen);

        return $lastSeen->diffInDays(Carbon::now()) > 7;
    }

    /**
     * Get all distinct file extensions from snapshots.
     *
     * @return Collection<int, string>
     */
    public function getExtensions(): Collection
    {
        return Cache::remember('snapshots_extensions', 60, function (): Collection {
            return Snapshot::pluck('path')
                ->map(fn (string $path): string => strtolower(pathinfo($path, PATHINFO_EXTENSION)))
                ->filter(fn (string $ext): bool => $ext !== '')
                ->unique()
                ->sort()
                ->values();
        });
    }

    /**
     * Recursively convert tree array to the desired format.
     *
     * @param array<string, array{path: string, name: string, children: array, file_count: int}> $tree
     * @return array<int, array{path: string, name: string, children: array, file_count: int}>
     */
    private function buildTreeArray(array $tree): array
    {
        $result = [];

        foreach ($tree as $key => $node) {
            $item = [
                'path' => $node['path'],
                'name' => $node['name'],
                'children' => $this->buildTreeArray($node['children']),
                'file_count' => $node['file_count'],
            ];
            $result[] = $item;
        }

        // Sort directories by name
        usort($result, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $result;
    }
}