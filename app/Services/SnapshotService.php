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

        return $query->orderByDesc('last_seen')->paginate($perPage);
    }

    /**
     * Build a nested directory tree from snapshot paths.
     * Only builds top-level (2 levels deep) for performance.
     * Deeper levels are loaded on demand via getSubDirectories().
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

        $tree = [];

        foreach ($paths as $path) {
            $parts = array_values(array_filter(explode('\\', $path)));

            if (count($parts) <= $watchDepth) {
                continue;
            }

            $relativeParts = array_slice($parts, $watchDepth);
            $maxLevel = min(count($relativeParts), 3);
            $current = &$tree;

            for ($i = 0; $i < $maxLevel; $i++) {
                $part = $relativeParts[$i];

                if (! isset($current[$part])) {
                    $fullParts = array_merge($watchDirParts, array_slice($relativeParts, 0, $i + 1));
                    $currentPath = implode('\\', $fullParts);
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
     * Get subdirectories for a given parent path (for lazy loading).
     *
     * @return array<int, array{path: string, name: string, children: array, file_count: int}>
     */
    public function getSubDirectories(string $parentPath): array
    {
        $parentPath = rtrim($parentPath, '\\/');
        $parentDepth = substr_count($parentPath, '\\') + 1;

        $paths = Snapshot::select('path')
            ->where('path', 'LIKE', $parentPath . '\\%')
            ->pluck('path')
            ->toArray();

        $tree = [];
        foreach ($paths as $path) {
            $cleanDir = preg_replace('/^[A-Z]:/i', '', $path);
            $cleanDir = ltrim($cleanDir, '\\/');
            $parts = array_values(array_filter(explode('\\', $cleanDir)));

            // Get the next level after parent
            $nextIndex = $parentDepth;
            if ($nextIndex >= count($parts)) {
                continue;
            }

            $part = $parts[$nextIndex];
            $subPath = implode('\\', array_slice($parts, 0, $nextIndex + 1));

            if (! isset($tree[$part])) {
                $isLeaf = ($nextIndex === count($parts) - 1);
                $tree[$part] = [
                    'path' => $subPath,
                    'name' => $part,
                    'children' => [],
                    'file_count' => 0,
                ];
                if ($isLeaf) {
                    $tree[$part]['file_count']++;
                }
            } elseif ($nextIndex === count($parts) - 1) {
                $tree[$part]['file_count']++;
            }
        }

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
     * Recursively sort tree nodes by name.
     *
     * @param array<int, array{path: string, name: string, children: array, file_count: int}> &$nodes
     */
    private function sortTree(array &$nodes): void
    {
        usort($nodes, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        foreach ($nodes as &$node) {
            if (! empty($node['children'])) {
                $this->sortTree($node['children']);
            }
        }
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