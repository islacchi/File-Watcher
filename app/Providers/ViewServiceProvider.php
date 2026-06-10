<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ConfigService;
use App\Services\EventService;
use App\Services\SnapshotService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     * Registers View Composers to inject shared data into Blade views.
     */
    public function boot(): void
    {
        // Share data with the main layout
        View::composer('components.layouts.app', function ($view): void {
            $config = app(ConfigService::class);
            $events = app(EventService::class);
            $snapshots = app(SnapshotService::class);

            $view->with([
                'watchDirectory' => $config->getWatchDirectory(),
                'scriptVersion' => $config->getScriptVersion(),
                'eventsCountToday' => $events->getTodayCount(),
                'staleCount' => $snapshots->getStaleCount(),
            ]);
        });
    }
}