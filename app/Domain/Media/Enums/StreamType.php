<?php

declare(strict_types=1);

namespace App\Domain\Media\Enums;

use App\Domain\Media\Support\YouTubeUrl;

/**
 * How a live channel's stream is played.
 *
 * - youtube: YouTube live/VOD id, played through the YouTube IFrame SDK
 * - hls:     .m3u8 manifest, played in a <video> (native on Safari, hls.js elsewhere)
 * - dash:    .mpd manifest, played in a <video> via dash.js
 * - file:    a plain progressive file (mp4/webm) the browser plays directly
 * - audio:   an audio-only radio stream (Icecast/Shoutcast/radiojar/…)
 * - iframe:  anything else that is only embeddable — no wrapped chrome possible
 *
 * A channel may leave its type unset, in which case `detect()` reads it off
 * the URL. Editors override when the URL does not give it away — which is the
 * normal case for radio: a radiojar/Icecast mount point carries no extension,
 * so nothing but the editor can tell it apart from a generic embed.
 */
enum StreamType: string
{
    case YouTube = 'youtube';
    case Hls = 'hls';
    case Dash = 'dash';
    case File = 'file';
    case Audio = 'audio';
    case Iframe = 'iframe';

    /** Best guess from the URL itself, for channels with no explicit type. */
    public static function detect(?string $url): self
    {
        $url = trim((string) $url);

        if ($url === '') {
            return self::Iframe;
        }

        // A bare 11-char id also counts as YouTube — YouTubeUrl accepts it.
        if (YouTubeUrl::videoId($url) !== null) {
            return self::YouTube;
        }

        // Match on the path only: a query string can carry an unrelated ".mpd"
        // (radiojar's own links end in ?rj-ttl=…&rj-tok=…, for instance).
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return match (true) {
            str_ends_with($path, '.m3u8') => self::Hls,
            str_ends_with($path, '.mpd') => self::Dash,
            (bool) preg_match('/\.(mp4|webm|ogv|mov)$/', $path) => self::File,
            (bool) preg_match('/\.(mp3|aac|m4a|ogg|oga|opus|flac|wav)$/', $path) => self::Audio,
            default => self::Iframe,
        };
    }

    /**
     * Which JS engine drives this source. File and audio share one: a <video>
     * element plays an audio-only source perfectly well, it simply paints
     * nothing — which is why the chrome, not the engine, handles the artwork.
     */
    public function engine(): string
    {
        return match ($this) {
            self::YouTube => 'youtube',
            self::Hls => 'hls',
            self::Dash => 'dash',
            self::File, self::Audio => 'media',
            self::Iframe => 'iframe',
        };
    }

    /** No picture to show — the player falls back to the channel artwork. */
    public function isAudioOnly(): bool
    {
        return $this === self::Audio;
    }

    /** True when the wrapped player can drive this source with its own chrome. */
    public function isWrappable(): bool
    {
        return $this !== self::Iframe;
    }

    /** Translation key for the admin label. */
    public function labelKey(): string
    {
        return 'admin.stream_type_'.$this->value;
    }
}
