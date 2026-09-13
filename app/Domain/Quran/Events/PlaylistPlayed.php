<?php

declare(strict_types=1);

namespace App\Domain\Quran\Events;

/** A playlist play that has passed the visitor deduplication check. */
final readonly class PlaylistPlayed
{
    public function __construct(public int $playlistId) {}
}
