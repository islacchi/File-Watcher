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

        $directory = $filters['directory'] ?? null;

        if ($directory) {
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
            $snapshotViewModels = $paginatedSnapshots->getCollection()->map(
                fn ($snapshot) => SnapshotViewModel::fromModel($snapshot),
            );
            $paginatedSnapshots->setCollection($snapshotViewModels);
        }

        $directoryTree = $this->snapshotService->getDirectoryTree();
        $extensions = $this->snapshotService->getExtensions();

        return view('snapshot.index', [
            'snapshots' => $paginatedSnapshots,
            'filters' => $filters,
            'directoryTree' => $directoryTree,
            'extensions' => $extensions,
            'currentDirectory' => $directory,
            'watchDirectory' => $this->configService->getWatchDirectory(),
        ]);
    }

    /**
     * Return the directory tree as rendered HTML for AJAX polling.
     */
    public function tree(): Response
    {
        $directoryTree = $this->snapshotService->getDirectoryTreeFresh();
        $currentDirectory = request()->input('directory');

        $html = view('snapshot._tree', compact('directoryTree', 'currentDirectory'))->render();

        return response($html)->header('Content-Type', 'text/html');
    }
}
