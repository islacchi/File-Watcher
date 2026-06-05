<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ConfigService;
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
        // Share watch directory and script version with the main layout
        View::composer('components.layouts.app', function ($view): void {
            $config = app(ConfigService::class);

            $view->with([
                'watchDirectory' => $config->getWatchDirectory(),
                'scriptVersion' => $config->getScriptVersion(),
            ]);
        });
    }
}