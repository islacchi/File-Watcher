<?php

declare(strict_types=1);

namespace App\View\Models;

use App\Enums\EventType;
use App\Models\Event;
use App\Services\Formatter;

class EventViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $timestamp,
        public readonly string $rawEventType,
        public readonly string $eventType,
        public readonly string $srcPath,
        public readonly ?string $destPath,
        public readonly ?int $fileSize,
        public readonly ?string $md5Hash,
        public readonly ?string $prevHash,
        public readonly bool $isOffline,
        public readonly string $badgeColor,
        public readonly string $badgeLabel,
        public readonly string $iconName,
        public readonly string $formattedSize,
        public readonly string $formattedTime,
        public readonly string $relativeTime,
        public readonly string $truncatedPath,
        public readonly string $truncatedHash,
        public readonly string $truncatedPrevHash,
        public readonly ?string $fileExtension,
        public readonly ?string $fileIcon,
    ) {}

    /**
     * Create an EventViewModel from an Event model.
     */
    public static function fromModel(Event $event): self
    {
        $isOffline = EventType::isOffline($event->event_type);
        $eventType = EventType::fromRaw($event->event_type);

        return new self(
            id: $event->id,
            timestamp: $event->timestamp,
            rawEventType: $event->event_type,
            eventType: $eventType?->value ?? $event->event_type,
            srcPath: $event->src_path,
            destPath: $event->dest_path,
            fileSize: $event->file_size,
            md5Hash: $event->md5_hash,
            prevHash: $event->prev_hash,
            isOffline: $isOffline,
            badgeColor: $eventType
                ? $eventType->effectiveBadgeColor($isOffline)
                : 'bg-gray-100 text-gray-600',
            badgeLabel: Formatter::badgeLabel($event->event_type),
            iconName: $eventType ? $eventType->icon() : 'question-mark-circle',
            formattedSize: Formatter::formatFileSize($event->file_size),
            formattedTime: Formatter::formatTimestamp($event->timestamp),
            relativeTime: Formatter::relativeTime($event->timestamp),
            truncatedPath: Formatter::truncatePath($event->src_path),
            truncatedHash: Formatter::truncateHash($event->md5_hash),
            truncatedPrevHash: Formatter::truncateHash($event->prev_hash),
            fileExtension: pathinfo($event->src_path, PATHINFO_EXTENSION) ?: null,
            fileIcon: Formatter::fileIconByExtension($event->src_path),
        );
    }

    /**
     * Check if this event shows a path change (rename/move).
     */
    public function hasPathChange(): bool
    {
        return $this->destPath !== null
            && in_array($this->eventType, ['RENAMED', 'MOVED', 'MOVED_AND_RENAMED'], true);
    }

    /**
     * Check if this event has hash before/after (modified events).
     */
    public function hasHashDiff(): bool
    {
        return $this->eventType === 'MODIFIED'
            && $this->prevHash !== null
            && $this->md5Hash !== null
            && $this->prevHash !== $this->md5Hash;
    }
}