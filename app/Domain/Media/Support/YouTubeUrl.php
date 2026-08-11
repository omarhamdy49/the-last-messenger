<?php

declare(strict_types=1);

namespace App\Domain\Media\Support;

/**
 * Pure helpers for parsing and building YouTube URLs.
 *
 * Episode video_url is canonically stored as the embed form
 * (https://www.youtube.com/embed/{id}) — the public player keys off that
 * format to switch to the YouTube iframe API.
 */
final class YouTubeUrl
{
    /** YouTube ids are exactly 11 url-safe base64 characters. */
    private const ID = '[A-Za-z0-9_-]{11}';

    /**
     * Extract the 11-character video id from a watch / embed / short /
     * youtu.be URL, or from a bare id. Returns null when none is found.
     */
    public static function videoId(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $patterns = [
            '#youtube\.com/embed/('.self::ID.')#',
            '#youtube\.com/shorts/('.self::ID.')#',
            '#youtube\.com/watch\?(?:.*&)?v=('.self::ID.')#',
            '#youtu\.be/('.self::ID.')#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value, $m) === 1) {
                return $m[1];
            }
        }

        // Bare id (no surrounding URL).
        if (preg_match('#^'.self::ID.'$#', $value) === 1) {
            return $value;
        }

        return null;
    }

    /** Extract the playlist id (list=...) from a URL, or null. */
    public static function playlistId(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('#[?&]list=([A-Za-z0-9_-]+)#', $value, $m) === 1) {
            return $m[1];
        }

        // Bare playlist id (PL..., UU..., OL..., etc.).
        if (preg_match('#^[A-Za-z0-9_-]{12,}$#', $value) === 1 && ! self::videoId($value)) {
            return $value;
        }

        return null;
    }

    public static function isPlaylist(?string $value): bool
    {
        return self::playlistId($value) !== null;
    }

    /**
     * Whether a value is a YouTube URL we will hand to yt-dlp — a playlist or
     * a single video. Host-locked on purpose: yt-dlp happily fetches hundreds
     * of other sites, and this value arrives from an admin form.
     */
    public static function isSupportedSource(?string $value): bool
    {
        if ($value === null || preg_match('#^https?://(www\.|m\.)?(youtube\.com|youtu\.be)/#i', $value) !== 1) {
            return false;
        }

        return self::isPlaylist($value) || self::videoId($value) !== null;
    }

    public static function embedUrl(string $videoId): string
    {
        return "https://www.youtube.com/embed/{$videoId}";
    }

    public static function watchUrl(string $videoId): string
    {
        return "https://www.youtube.com/watch?v={$videoId}";
    }

    /**
     * YouTube CDN thumbnail. hqdefault (480x360) exists for every video,
     * unlike maxresdefault which 404s on many.
     */
    public static function thumbnailUrl(string $videoId): string
    {
        return "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";
    }

    public static function playlistUrl(string $playlistId): string
    {
        return "https://www.youtube.com/playlist?list={$playlistId}";
    }
}
