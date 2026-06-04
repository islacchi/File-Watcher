<?php

declare(strict_types=1);

namespace App\View\Models;

use App\Models\Snapshot;
use App\Services\Formatter;
use App\Services\SnapshotService;

class SnapshotViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $path,
        public readonly ?int $size,
        public readonly ?float $mtime,
        public readonly ?string $md5Hash,
        public readonly string $lastSeen,
        public readonly string $formattedSize,
        public readonly string $truncatedHash,
        public readonly string $formattedLastSeen,
        public readonly string $relativeLastSeen,
        public readonly string $truncatedPath,
        public readonly bool $isStale,
        public readonly ?string $fileExtension,
    ) {}

    /**
     * Create a SnapshotViewModel from a Snapshot model.
     */
    public static function fromModel(Snapshot $snapshot): self
    {
        return new self(
            id: $snapshot->id,
            path: $snapshot->path,
            size: $snapshot->size,
            mtime: $snapshot->mtime,
            md5Hash: $snapshot->md5_hash,
            lastSeen: $snapshot->last_seen,
            formattedSize: Formatter::formatFileSize($snapshot->size),
            truncatedHash: Formatter::truncateHash($snapshot->md5_hash),
            formattedLastSeen: Formatter::formatTimestamp($snapshot->last_seen),
            relativeLastSeen: Formatter::relativeTime($snapshot->last_seen),
            truncatedPath: Formatter::truncatePath($snapshot->path),
            isStale: SnapshotService::isStale($snapshot),
            fileExtension: pathinfo($snapshot->path, PATHINFO_EXTENSION) ?: null,
        );
    }
}