<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Media\DTOs\YouTubePlaylistData;
use App\Domain\Media\DTOs\YouTubeVideoData;
use App\Domain\Media\Support\YouTubeClient;
use App\Domain\Media\Support\YouTubeUrl;

/**
 * In-memory YouTubeClient for tests — no yt-dlp/network. Returns canned
 * metadata, a configurable playlist, and an optional local thumbnail path.
 */
final class FakeYouTubeClient extends YouTubeClient
{
    /**
     * @param  array<string, YouTubeVideoData>  $videos  keyed by video id
     */
    public function __construct(
        private array $videos = [],
        private ?YouTubePlaylistData $playlist = null,
        private ?string $thumbnailPath = null,
        private ?string $transcript = 'fake transcript text',
    ) {}

    public function setPlaylist(YouTubePlaylistData $playlist): void
    {
        $this->playlist = $playlist;
    }

    public function fetchVideo(string $urlOrId): YouTubeVideoData
    {
        $id = YouTubeUrl::videoId($urlOrId) ?? $urlOrId;

        return $this->videos[$id] ?? new YouTubeVideoData(
            id: $id,
            title: "عنوان {$id}",
            description: "وصف الحلقة {$id}",
            durationSeconds: 1800,
            channel: 'قناة تجريبية',
            uploadDate: '20260101',
            thumbnailUrl: "https://img.example/{$id}.jpg",
        );
    }

    public function fetchPlaylist(string $url): YouTubePlaylistData
    {
        return $this->playlist ?? new YouTubePlaylistData(
            id: 'PLfake',
            title: 'قائمة تجريبية',
            description: 'وصف القائمة',
            channel: 'قناة تجريبية',
            thumbnailUrl: 'https://img.example/playlist.jpg',
            entries: [],
        );
    }

    public function fetchTranscript(string $urlOrId): ?string
    {
        return $this->transcript;
    }

    public function downloadThumbnail(?string $url, ?string $videoId = null): ?string
    {
        if ($this->thumbnailPath === null) {
            return null;
        }

        // Hand back a *copy* — the service moves the file into the collection.
        $copy = tempnam(sys_get_temp_dir(), 'ytthumb').'.png';
        copy($this->thumbnailPath, $copy);

        return $copy;
    }
}
