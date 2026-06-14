<?php

declare(strict_types=1);

namespace App\Domain\Media\DTOs;

/**
 * A single item in a YouTube playlist as returned by
 * `yt-dlp --flat-playlist` (lightweight: id + title + position only).
 */
final readonly class YouTubePlaylistEntry
{
    public function __construct(
        public string $id,
        public string $title,
        public int $position, // 1-based order within the playlist
    ) {}
}
