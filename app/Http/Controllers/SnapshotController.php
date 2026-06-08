<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SnapshotFilterRequest;
use App\Services\ConfigService;
use App\Services\SnapshotService;
use App\View\Models\SnapshotViewModel;
use Illuminate\Http\Response;

class SnapshotController extends Controller
{
    public function __construct(
        private readonly SnapshotService $snapshotService,
        private readonly ConfigService $configService,
    ) {}

    /**
     * Display the snapshot view.
     */
    public function index(SnapshotFilterRequest $request)
    {
        $filters = $request->validated();

        // Directory and path are base64-encoded to preserve UNC paths with backslashes
        $directory = $this->decodeBase64Param($filters['directory'] ?? null);
        $filePath = $this->decodeBase64Param($filters['path'] ?? null);

        $paginatedSnapshots = null;
        $singleSnapshot = null;

        if ($filePath !== null) {
            // Single-file view: filter table to show only this one file
            $filters['path'] = $filePath;
            $paginatedSnapshots = $this->snapshotService->getFilteredSnapshots($filters, perPage: 50);
            $paginatedSnapshots->setCollection(
                $paginatedSnapshots->getCollection()->map(
                    fn ($snapshot) => SnapshotViewModel::fromModel($snapshot),
                )
            );
            $singleSnapshot = $this->snapshotService->getSnapshotByPath($filePath);
        } elseif ($directory) {
            $snapshots = $this->snapshotService->getFilesInDirectory($directory);
            $snapshotViewModels = $snapshots->map(
                fn ($snapshot) => SnapshotViewModel::fromModel($snapshot),
            );

            $paginatedSnapshots = new \Illuminate\Pagination\LengthAwarePaginator(
                $snapshotViewModels,
                $snapshotViewModels->count(),
                50,
                $request->input('page', 1),
            );
        } else {
            $paginatedSnapshots = $this->snapshotService->getFilteredSnapshots($filters, perPage: 50);
            $paginatedSnapshots->setCollection(
                $paginatedSnapshots->getCollection()->map(
                    fn ($snapshot) => SnapshotViewModel::fromModel($snapshot),
                )
            );
        }

        $directoryTree = $this->snapshotService->getDirectoryTree();
        $extensions = $this->snapshotService->getExtensions();

        return view('snapshot.index', [
            'snapshots' => $paginatedSnapshots,
            'filters' => $filters,
            'directoryTree' => $directoryTree,
            'extensions' => $extensions,
            'currentDirectory' => $directory,
            'currentFilePath' => $filePath,
            'singleSnapshot' => $singleSnapshot,
            'watchDirectory' => $this->configService->getWatchDirectory(),
        ]);
    }

    /**
     * Decode a base64-encoded query parameter back to its original string.
     * Falls back to the original value if decoding fails.
     */
    private function decodeBase64Param(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $decoded = base64_decode($value, true);
        return $decoded !== false ? $decoded : $value;
    }

    /**
     * Return the directory tree as rendered HTML for AJAX polling.
     * Called every 15 seconds by Alpine.js on the snapshot page.
     * Falls back to cached tree if the fresh build fails.
     */
    public function tree(): Response
    {
        $directoryTree = $this->snapshotService->getDirectoryTreeFresh();
        $currentDirectory = request()->input('directory');

        $html = view('snapshot._tree', compact('directoryTree', 'currentDirectory'))->render();

        return response($html)->header('Content-Type', 'text/html');
    }
}
