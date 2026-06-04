<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case CREATED = 'CREATED';
    case MODIFIED = 'MODIFIED';
    case DELETED = 'DELETED';
    case RENAMED = 'RENAMED';
    case MOVED = 'MOVED';
    case MOVED_AND_RENAMED = 'MOVED_AND_RENAMED';

    /**
     * Get the offline variant of this event type.
     */
    public function offline(): string
    {
        return $this->value . ' (offline)';
    }

    /**
     * Parse a raw event_type string into an EventType enum case.
     * Returns null if the type is not recognized.
     */
    public static function fromRaw(string $raw): ?self
    {
        $cleaned = str_replace(' (offline)', '', $raw);

        return self::tryFrom($cleaned);
    }

    /**
     * Check if a raw event type string represents an offline event.
     */
    public static function isOffline(string $raw): bool
    {
        return str_contains($raw, '(offline)');
    }

    /**
     * Get the human-readable label for this event type.
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Created',
            self::MODIFIED => 'Modified',
            self::DELETED => 'Deleted',
            self::RENAMED => 'Renamed',
            self::MOVED => 'Moved',
            self::MOVED_AND_RENAMED => 'Moved & Renamed',
        };
    }

    /**
     * Get the Tailwind CSS badge color class for this event type.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::CREATED => 'bg-green-100 text-green-800',
            self::MODIFIED => 'bg-blue-100 text-blue-800',
            self::DELETED => 'bg-red-100 text-red-800',
            self::RENAMED => 'bg-purple-100 text-purple-800',
            self::MOVED => 'bg-teal-100 text-teal-800',
            self::MOVED_AND_RENAMED => 'bg-teal-100 text-purple-800',
        };
    }

    /**
     * Get the Tailwind CSS badge color class for offline variants.
     */
    public function offlineBadgeColor(): string
    {
        return 'bg-gray-100 text-gray-600';
    }

    /**
     * Get the badge color class based on whether the event is offline.
     */
    public function effectiveBadgeColor(bool $offline = false): string
    {
        return $offline ? $this->offlineBadgeColor() : $this->badgeColor();
    }

    /**
     * Get the Heroicon name for this event type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::CREATED => 'plus-circle',
            self::MODIFIED => 'pencil-square',
            self::DELETED => 'trash',
            self::RENAMED => 'document-text',
            self::MOVED => 'arrow-right-circle',
            self::MOVED_AND_RENAMED => 'arrow-path',
        };
    }

    /**
     * Get all available event types as an associative array for filter dropdowns.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}