<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Config;
use Illuminate\Support\Facades\Cache;

class ConfigService
{
    /**
     * Get a single config value by key.
     */
    public function get(string $key): ?string
    {
        $all = $this->getAll();

        return $all[$key] ?? null;
    }

    /**
     * Get all config values as an associative array.
     * Cached for 60 seconds to avoid hammering the DB on every page load.
     *
     * @return array<string, string>
     */
    public function getAll(): array
    {
        return (array) Cache::remember('config_all', 60, function (): array {
            return Config::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get the watched directory path.
     * Falls back to 'K:\' if the config table has no entry yet.
     */
    public function getWatchDirectory(): string
    {
        return $this->get('watch_directory') ?? 'K:\\';
    }

    /**
     * Get the script start time from the config table.
     * This is the actual time the Python script started, written on every startup.
     */
    public function getStartedAt(): ?string
    {
        return $this->get('started_at');
    }

    /**
     * Get the script version.
     */
    public function getScriptVersion(): ?string
    {
        return $this->get('script_version');
    }

    /**
     * Get the retention days setting.
     */
    public function getRetentionDays(): ?string
    {
        return $this->get('retention_days');
    }

    /**
     * Get the log directory path.
     */
    public function getLogDirectory(): ?string
    {
        return $this->get('log_directory');
    }

    /**
     * Clear the config cache. Call this if you need fresh data.
     */
    public function clearCache(): void
    {
        Cache::forget('config_all');
    }
}