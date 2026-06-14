<?php

declare(strict_types=1);

namespace App\Domain\Media\Enums;

/**
 * Lifecycle of a queued YouTube playlist import.
 */
enum YouTubeSyncStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Failed;
    }

    /** Translation key for the admin progress UI. */
    public function labelKey(): string
    {
        return 'admin.yt_status_'.$this->value;
    }
}
