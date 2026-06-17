<?php

declare(strict_types=1);

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\SnapshotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filewatcher.dashboard');
});

Route::prefix('filewatcher')->name('filewatcher.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/events', [EventController::class, 'index'])->name('events');
    Route::get('/files', [FileController::class, 'timeline'])->name('files.timeline');
    Route::get('/snapshot/tree', [SnapshotController::class, 'tree'])->name('snapshot.tree');
    Route::get('/snapshot', [SnapshotController::class, 'index'])->name('snapshot');
    Route::get('/event-counts', [EventController::class, 'todayCounts'])->name('event-counts');
    Route::get('/latest-event', [EventController::class, 'latestEvent'])->name('latest-event');
    Route::get('/health', [HealthController::class, 'check'])->name('health');
});