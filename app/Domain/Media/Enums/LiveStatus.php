<?php

declare(strict_types=1);

namespace App\Domain\Media\Enums;

/**
 * Lifecycle status of a live channel.
 *
 * - live: currently broadcasting (shows the red LIVE badge)
 * - scheduled: will broadcast later
 * - offline: not broadcasting
 */
enum LiveStatus: string
{
    case Live = 'live';
    case Scheduled = 'scheduled';
    case Offline = 'offline';

    /** Translation key for the public/admin label. */
    public function labelKey(): string
    {
        return 'media.public.live_status_'.$this->value;
    }

    public function isLive(): bool
    {
        return $this === self::Live;
    }
}
