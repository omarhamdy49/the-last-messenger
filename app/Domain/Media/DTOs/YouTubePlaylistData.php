<?php

declare(strict_types=1);

namespace App\Domain\Media\DTOs;

/**
 * Normalized YouTube playlist metadata + flat entry list, parsed from
 * `yt-dlp --flat-playlist --dump-single-json`.
 */
final readonly class YouTubePlaylistData
{
    /**
     * @param  list<YouTubePlaylistEntry>  $entries
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public ?string $channel,
        public ?string $thumbnailUrl,
        public array $entries,
    ) {}

    /**
     * @param  array<string, mixed>  $json
     */
    public static function fromArray(array $json): self
    {
        $entries = [];
        $position = 0;

        foreach (($json['entries'] ?? []) as $entry) {
            if (! is_array($entry) || empty($entry['id'])) {
                continue;
            }

            $position++;
            $entries[] = new YouTubePlaylistEntry(
                id: (string) $entry['id'],
                title: trim((string) ($entry['title'] ?? '')),
                position: $position,
            );
        }

        return new self(
            id: (string) ($json['id'] ?? ''),
            title: trim((string) ($json['title'] ?? '')),
            description: isset($json['description']) ? trim((string) $json['description']) : null,
            channel: $json['channel'] ?? $json['uploader'] ?? null,
            thumbnailUrl: self::largestThumbnail($json['thumbnails'] ?? null),
            entries: $entries,
        );
    }

    public function count(): int
    {
        return count($this->entries);
    }

    /**
     * yt-dlp lists thumbnails smallest→largest; take the last with a url.
     *
     * @param  mixed  $thumbnails
     */
    private static function largestThumbnail($thumbnails): ?string
    {
        if (! is_array($thumbnails)) {
            return null;
        }

        for ($i = count($thumbnails) - 1; $i >= 0; $i--) {
            if (! empty($thumbnails[$i]['url'])) {
                return (string) $thumbnails[$i]['url'];
            }
        }

        return null;
    }
}
