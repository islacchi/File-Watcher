<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventType;
use Carbon\Carbon;

class Formatter
{
    /**
     * Format a byte count into a human-readable file size.
     */
    public static function formatFileSize(?int $bytes): string
    {
        if ($bytes === null || $bytes === 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        $size = $bytes / (1024 ** $power);

        return round($size, $power === 0 ? 0 : 1) . ' ' . $units[$power];
    }

    /**
     * Format an ISO 8601 timestamp into a readable local format.
     */
    public static function formatTimestamp(?string $iso8601): string
    {
        if ($iso8601 === null || $iso8601 === '') {
            return '—';
        }

        return Carbon::parse($iso8601)->format('M j, Y g:i:s A');
    }

    /**
     * Format an ISO 8601 timestamp into a short date-time format.
     */
    public static function formatTimestampShort(?string $iso8601): string
    {
        if ($iso8601 === null || $iso8601 === '') {
            return '—';
        }

        return Carbon::parse($iso8601)->format('M j, g:i A');
    }

    /**
     * Get a relative time string (e.g., "2 hours ago").
     */
    public static function relativeTime(?string $iso8601): string
    {
        if ($iso8601 === null || $iso8601 === '') {
            return '—';
        }

        return Carbon::parse($iso8601)->diffForHumans();
    }

    /**
     * Truncate a file path in the middle, showing start and end.
     * e.g., "/home/user/documents/projects/file.pdf" → "/home/user…/file.pdf"
     */
    public static function truncatePath(string $path, int $maxLen = 60): string
    {
        if (mb_strlen($path) <= $maxLen) {
            return $path;
        }

        $halfLen = (int) floor(($maxLen - 1) / 2);
        $start = mb_substr($path, 0, $halfLen);
        $end = mb_substr($path, -$halfLen);

        return $start . '…' . $end;
    }

    /**
     * Truncate an MD5 hash to the first 8 characters.
     */
    public static function truncateHash(?string $hash): string
    {
        if ($hash === null || $hash === '') {
            return '—';
        }

        if (mb_strlen($hash) <= 8) {
            return $hash;
        }

        return mb_substr($hash, 0, 32);
    }

    /**
     * Get the Tailwind CSS badge color class for an event type.
     */
    public static function badgeColor(EventType $type, bool $offline = false): string
    {
        return $type->effectiveBadgeColor($offline);
    }

    /**
     * Get the badge label for an event type, including "(offline)" suffix if applicable.
     */
    public static function badgeLabel(string $rawEventType): string
    {
        $type = EventType::fromRaw($rawEventType);

        if ($type === null) {
            return $rawEventType;
        }

        $label = $type->label();

        if (EventType::isOffline($rawEventType)) {
            $label .= ' (offline)';
        }

        return $label;
    }

    /**
     * Get the Heroicon SVG name for an event type.
     */
    public static function eventIcon(EventType $type): string
    {
        return $type->icon();
    }

    /**
     * Calculate trend percentage and direction.
     *
     * @return array{value: string, direction: string}
     */
    public static function trendPercent(int $current, int $previous): array
    {
        if ($previous === 0) {
            return $current > 0
                ? ['value' => '+100%', 'direction' => 'up']
                : ['value' => '0%', 'direction' => 'neutral'];
        }

        $change = (($current - $previous) / $previous) * 100;
        $rounded = round($change);

        if ($rounded > 0) {
            return ['value' => '+' . $rounded . '%', 'direction' => 'up'];
        }

        if ($rounded < 0) {
            return ['value' => $rounded . '%', 'direction' => 'down'];
        }

        return ['value' => '0%', 'direction' => 'neutral'];
    }

    /**
     * Get the file extension from a path.
     */
    public static function getExtension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Get a Heroicon name based on file extension.
     */
    public static function fileIconByExtension(string $path): string
    {
        $ext = self::getExtension($path);

        return match (true) {
            in_array($ext, ['pdf']) => 'document-text',
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']) => 'photo',
            in_array($ext, ['mp4', 'avi', 'mov', 'mkv', 'wmv']) => 'film',
            in_array($ext, ['mp3', 'wav', 'flac', 'aac', 'ogg']) => 'musical-note',
            in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz']) => 'archive-box',
            in_array($ext, ['xls', 'xlsx', 'csv']) => 'table-cells',
            in_array($ext, ['doc', 'docx', 'txt', 'rtf']) => 'document',
            in_array($ext, ['ppt', 'pptx']) => 'presentation-chart-bar',
            in_array($ext, ['exe', 'msi', 'bat', 'cmd', 'ps1']) => 'cpu-chip',
            default => 'document',
        };
    }

    /**
     * Format a Unix timestamp (mtime) to ISO 8601.
     */
    public static function mtimeToIso(?float $mtime): ?string
    {
        if ($mtime === null || $mtime === 0.0) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $mtime)->toIso8601String();
    }
}